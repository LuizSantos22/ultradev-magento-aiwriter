<?php
/**
 * Camada OPCIONAL de enriquecimento de contexto via Exa Search.
 *
 * Esta classe nunca lança exceção para o chamador: qualquer falha
 * (desativado, sem API key, timeout, erro HTTP, JSON inválido) resulta
 * em retorno null, e o fluxo principal de geração (Gemini/DeepSeek)
 * segue normalmente sem o contexto extra — exatamente como funcionava
 * antes desta integração existir.
 */
class UltraDev_AIWriter_Helper_Exa extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'https://api.exa.ai/search';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag('ultradev_aiwriter/exa/enabled');
    }

    /**
     * Busca contexto técnico real sobre o produto. Retorna null se a
     * funcionalidade estiver desativada ou se a busca falhar por
     * qualquer motivo — nunca propaga erro.
     *
     * @param  string $productName
     * @return string|null  Bloco de texto pronto para injeção no prompt
     */
    public function fetchContext($productName)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $apiKey = Mage::helper('core')->decrypt(
            Mage::getStoreConfig('ultradev_aiwriter/exa/api_key')
        );
        if (empty($apiKey)) {
            Mage::log('UltraDev_AIWriter (Exa): ativado mas sem API key configurada — seguindo sem contexto extra.', Zend_Log::WARN, 'ultradev_aiwriter.log');
            return null;
        }

        $timeout    = (int) Mage::getStoreConfig('ultradev_aiwriter/exa/timeout');
        $timeout    = $timeout > 0 ? $timeout : 8;
        $numResults = (int) Mage::getStoreConfig('ultradev_aiwriter/exa/num_results');
        $numResults = $numResults > 0 ? $numResults : 5;

        $payload = array(
            'query'      => trim($productName) . ' especificações técnicas ficha técnica review',
            'type'       => 'auto',
            'numResults' => $numResults,
            'contents'   => array(
                'highlights' => true,
            ),
        );

        try {
            $response = $this->_httpPost(self::ENDPOINT, json_encode($payload), array(
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
            ), $timeout);

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($data['results'])) {
                Mage::log('UltraDev_AIWriter (Exa): resposta sem resultados utilizáveis — seguindo sem contexto extra.', Zend_Log::WARN, 'ultradev_aiwriter.log');
                return null;
            }

            return $this->_formatContext($data['results']);

        } catch (Exception $e) {
            Mage::log('UltraDev_AIWriter (Exa) falhou — seguindo sem contexto extra: ' . $e->getMessage(), Zend_Log::WARN, 'ultradev_aiwriter.log');
            return null;
        }
    }

    /**
     * @param  array $results
     * @return string|null
     */
    protected function _formatContext(array $results)
    {
        $lines = array();
        foreach ($results as $result) {
            if (!empty($result['highlights']) && is_array($result['highlights'])) {
                $lines[] = '- ' . implode(' ', $result['highlights']);
            }
        }
        return $lines ? implode("\n", $lines) : null;
    }

    /**
     * @param  string $url
     * @param  string $body
     * @param  array  $headers
     * @param  int    $timeout
     * @return string
     * @throws Exception
     */
    protected function _httpPost($url, $body, array $headers, $timeout)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception('cURL error: ' . $curlErr);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("HTTP $httpCode: " . substr((string) $response, 0, 300));
        }
        return $response;
    }
}
