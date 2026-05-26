<?php
class UltraDev_AIWriter_Adminhtml_AiwriterController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/products');
    }

    /**
     * AJAX: gera o conteúdo via IA e retorna JSON para o preview.
     */
    public function generateAction()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $productName     = $this->getRequest()->getPost('product_name');
            $referenceId     = (int) $this->getRequest()->getPost('reference_id');

            if (empty($productName)) {
                throw new Exception('Informe o nome do produto.');
            }

            // Produto de referência
            if (!$referenceId) {
                $referenceId = (int) Mage::getStoreConfig('ultradev_aiwriter/general/reference_product_id');
            }
            if (!$referenceId) {
                throw new Exception('Nenhum produto de referência configurado. Defina nas configurações ou selecione um produto.');
            }

            $refProduct = Mage::getModel('catalog/product')->load($referenceId);
            if (!$refProduct->getId()) {
                throw new Exception("Produto de referência ID {$referenceId} não encontrado.");
            }

            $reference = [
                'name'              => $refProduct->getName(),
                'description'       => $refProduct->getDescription(),
                'short_description' => $refProduct->getShortDescription(),
            ];

            // Monta e envia prompt
            $prompt   = Mage::helper('ultradev_aiwriter/prompt')->build($productName, $reference);
            $rawJson  = Mage::helper('ultradev_aiwriter/api')->generate($prompt);

            // Limpa possível markdown residual (```json ... ```)
            $rawJson  = preg_replace('/^```(?:json)?\s*/m', '', $rawJson);
            $rawJson  = preg_replace('/\s*```\s*$/m', '', $rawJson);
            $rawJson  = trim($rawJson);

            $generated = json_decode($rawJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('A IA retornou um JSON inválido: ' . json_last_error_msg() . "\n\nResposta raw:\n" . substr($rawJson, 0, 500));
            }

            $this->getResponse()->setBody(json_encode([
                'success' => true,
                'data'    => $generated,
            ]));

        } catch (Exception $e) {
            Mage::log('UltraDev_AIWriter generate error: ' . $e->getMessage(), Zend_Log::ERR, 'ultradev_aiwriter.log');
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * AJAX: busca produtos para o autocomplete do campo de referência.
     */
    public function searchProductAction()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        $query    = $this->getRequest()->getPost('q', '');
        $results  = [];

        if (strlen($query) >= 2) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(['name'])
                ->addAttributeToFilter('name', ['like' => '%' . $query . '%'])
                ->setPageSize(10);

            foreach ($collection as $product) {
                $results[] = [
                    'id'   => $product->getId(),
                    'name' => $product->getName(),
                ];
            }
        }

        $this->getResponse()->setBody(json_encode($results));
    }
}
