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

{
"name": "título completo do produto para o campo Nome",
"meta_title": "título SEO (máx 60 chars)",
"meta_description": "descrição SEO atraente (máx 160 chars)",
"meta_keywords": "palavra1, palavra2, palavra3, ...",
"meta_page_description": "descrição completa para meta page (2-3 frases técnicas)",
"short_description": "HTML completo do short description",
"description": "HTML completo do long description"
}

## REGRAS OBRIGATÓRIAS

1. **short_description**: Use exatamente esta estrutura HTML (adapte o conteúdo):
```html
<br><p class="ultrd-short-description">Texto introdutório com <strong>Nome do Produto</strong> e destaques principais.</p>
<div class="ultrd-badges"><span>Badge 1</span><span>Badge 2</span><span>Badge 3</span></div>
```

2. **description**: Use exatamente esta estrutura HTML completa (adapte o conteúdo):
```html
<div class="ultrd-product-description">

    <div class="ultrd-hero">
        <div class="ultrd-hero-text">
            <img class="ultrd-brand-logo" src="URL_LOGO_MARCA" alt="Logo Marca">
            <h3 class="ultrd-hero-title">Título Hero do Produto</h3>
            <p>Parágrafo introdutório 1 com <strong>destaques</strong>.</p>
            <p>Parágrafo introdutório 2 com mais detalhes técnicos.</p>
        </div>
        <div class="ultrd-hero-media">
            <div class="ultrd-video-wrapper">
                <iframe src="https://www.youtube.com/embed/SEARCH_TERM_PRODUTO" allowfullscreen></iframe>
            </div>
            <p class="ultrd-video-caption"><strong>Dica de Especialista:</strong> Texto da dica.</p>
        </div>
    </div>

    <div class="ultrd-section">
        <h4 class="ultrd-section-title">Título da Seção Principal</h4>
        <p>Parágrafo 1 detalhado com <strong>termos técnicos</strong>.</p>
        <p>Parágrafo 2 com mais contexto.</p>
        <p>Parágrafo 3 sobre conectividade/recursos extras.</p>
    </div>

    <div class="ultrd-features">
        <div class="ultrd-feature-card">
            <h4>Feature 1</h4>
            <p>Descrição da feature 1.</p>
        </div>
        <div class="ultrd-feature-card">
            <h4>Feature 2</h4>
            <p>Descrição da feature 2.</p>
        </div>
        <div class="ultrd-feature-card">
            <h4>Feature 3</h4>
            <p>Descrição da feature 3.</p>
        </div>
    </div>

    <div class="ultrd-box">
        <h3 class="ultrd-box-title">Especificações Técnicas</h3>
        <div class="ultrd-spec-grid">
            <div class="ultrd-spec-col">
                <h4>Categoria 1</h4>
                <ul>
                    <li><strong>Spec:</strong> Valor</li>
                </ul>
            </div>
            <div class="ultrd-spec-col">
                <h4>Categoria 2</h4>
                <ul>
                    <li><strong>Spec:</strong> Valor</li>
                </ul>
            </div>
            <div class="ultrd-spec-col">
                <h4>Categoria 3</h4>
                <ul>
                    <li><strong>Spec:</strong> Valor</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="ultrd-box">
        <h3 class="ultrd-box-title">Conteúdo da Embalagem</h3>
        <div class="ultrd-content-grid">
            <div>
                <ul>
                    <li>Item 1</li>
                    <li>Item 2</li>
                </ul>
            </div>
            <div class="ultrd-note-box">
                <p><strong>Nota Profissional:</strong> Equipamento original. Todos os itens listados são os acessórios oficiais.</p>
            </div>
        </div>
        <div class="ultrd-import-alert">
            <div class="ultrd-alert-icon">!</div>
            <div>
                <p class="ultrd-alert-title">Item importado com todas as eventuais taxas de importação já incluídas.</p>
                <p>Se houver qualquer cobrança adicional, todas as taxas serão pagas pelo nosso site: <a href="/reembolso-impostos">Clique Aqui</a></p>
            </div>
        </div>
    </div>

</div>
```

3. Para o logo da marca, use a URL real do Wikimedia Commons ou CDN público conhecido para a marca do produto. Se não souber a URL exata, use uma URL plausível no formato: `https://upload.wikimedia.org/wikipedia/commons/thumb/.../Logo.png`

4. Para o iframe do YouTube, use como src: `https://www.youtube.com/embed/?search={nome_do_produto_sem_espacos}` — deixe como placeholder que será substituído manualmente.

5. Escreva em **português brasileiro**, tom técnico-comercial, focado em benefícios reais do produto.

6. Use conhecimento real sobre o produto **{$productName}** para preencher specs, features e conteúdo — seja preciso e específico.

## PRODUTO DE REFERÊNCIA (apenas para formato)

Short description do produto de referência:
{$refShort}

Long description do produto de referência (estrutura a seguir):
{$refLong}

---

Agora gere o JSON completo para: **{$productName}**
PROMPT;
    }
}
