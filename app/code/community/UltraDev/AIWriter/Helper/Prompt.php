<?php
class UltraDev_AIWriter_Helper_Prompt extends Mage_Core_Helper_Abstract
{
    /**
     * Monta o prompt completo enviado à IA.
     *
     * @param  string $productName   Nome do produto a ser criado
     * @param  array  $reference     Dados do produto de referência
     * @return string
     */
    public function build($productName, array $reference)
    {
        $refShort = strip_tags($reference['short_description']);
        $refLong  = $reference['description'];
        $refTitle = $reference['name'];

        return <<<PROMPT
Você é um redator especialista em e-commerce brasileiro de eletrônicos de alta performance.

Sua tarefa é criar um anúncio completo para o produto: **{$productName}**

Use o anúncio do produto "{$refTitle}" como referência EXCLUSIVA de formato, estrutura HTML, tom de voz e estilo de escrita. NÃO copie o conteúdo — apenas o formato.

## FORMATO DE SAÍDA

Responda SOMENTE com um objeto JSON válido, sem markdown, sem texto antes ou depois, com exatamente estas chaves:
