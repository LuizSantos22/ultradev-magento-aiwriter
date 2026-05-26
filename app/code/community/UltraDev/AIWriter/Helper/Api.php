<?php
class UltraDev_AIWriter_Helper_Api extends Mage_Core_Helper_Abstract
{
    const GEMINI_ENDPOINT   = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    const DEEPSEEK_ENDPOINT = 'https://api.deepseek.com/v1/chat/completions';
    const DEEPSEEK_MODEL    = 'deepseek-chat';

    public function generate($prompt)
    {
        $primary = Mage::getStoreConfig('ultradev_aiwriter/general/primary_api');
        $order   = ($primary === 'deepseek') ? ['deepseek', 'gemini'] : ['gemini', 'deepseek'];

        $lastError = null;
        foreach ($order as $api) {
            try {
                return ($api === 'gemini') ? $this->_callGemini($prompt) : $this->_callDeepSeek($prompt);
            } catch (Exception $e) {
                $lastError = $e;
                Mage::log("UltraDev_AIWriter: $api falhou — " . $e->getMessage(), Zend_Log::WARN, 'ultradev_aiwriter.log');
            }
        }
        throw new Exception('Todas as APIs falharam. Último erro: ' . $lastError->getMessage());
    }

    protected function _callGemini($prompt)
    {
        $apiKey = Mage::helper('core')->decrypt(
            Mage::getStoreConfig('ultradev_aiwriter/general/gemini_api_key')
        );
        if (empty($apiKey)) throw new Exception('Gemini API Key não configurada.');

        $url  = self::GEMINI_ENDPOINT . '?key=' . urlencode($apiKey);
        $body = json_encode([
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 8192],
        ]);

        $response = $this->_httpPost($url, $body, ['Content-Type: application/json']);
        $data     = json_decode($response, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Gemini: resposta inesperada — ' . substr($response, 0, 200));
        }
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    protected function _callDeepSeek($prompt)
    {
        $apiKey = Mage::helper('core')->decrypt(
            Mage::getStoreConfig('ultradev_aiwriter/general/deepseek_api_key')
        );
        if (empty($apiKey)) throw new Exception('DeepSeek API Key não configurada.');

        $body = json_encode([
            'model'       => self::DEEPSEEK_MODEL,
            'messages'    => [
                ['role' => 'system', 'content' => 'Você é especialista em anúncios para e-commerce brasileiro. Responda SOMENTE com o JSON solicitado, sem markdown, sem texto extra.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'max_tokens'  => 8192,
            'temperature' => 0.7,
        ]);

        $response = $this->_httpPost(self::DEEPSEEK_ENDPOINT, $body, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        $data = json_decode($response, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('DeepSeek: resposta inesperada — ' . substr($response, 0, 200));
        }
        return $data['choices'][0]['message']['content'];
    }

    protected function _httpPost($url, $body, array $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) throw new Exception('cURL error: ' . $curlErr);
        if ($httpCode < 200 || $httpCode >= 300) throw new Exception("HTTP $httpCode: " . substr($response, 0, 300));
        return $response;
    }
}
