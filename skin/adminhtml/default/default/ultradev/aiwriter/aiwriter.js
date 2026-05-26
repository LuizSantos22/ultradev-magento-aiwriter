;(function () {
    'use strict';

    // ── Aguarda o DOM e o objeto de configuração ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof UltraDevAIWriter === 'undefined') return;
        AIWriter.init();
    });

    var AIWriter = {

        cfg: null,
        generated: null,
        refSearchTimer: null,

        init: function () {
            this.cfg = UltraDevAIWriter;
            this._injectButton();
            this._bindEvents();
        },

        // ── Injeta botão "Criar com IA" após o campo Nome ─────────────────
        _injectButton: function () {
            var nameField = document.getElementById('name');
            if (!nameField) return;

            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.id        = 'aiwriter-open-btn';
            btn.innerHTML = '✨ Criar com IA';
            btn.style.cssText = [
                'margin-left:10px',
                'padding:5px 14px',
                'background:#6a4fc6',
                'color:#fff',
                'border:none',
                'border-radius:4px',
                'cursor:pointer',
                'font-size:13px',
                'font-weight:bold',
                'vertical-align:middle',
            ].join(';');

            nameField.parentNode.insertBefore(btn, nameField.nextSibling);
        },

        // ── Todos os event listeners ───────────────────────────────────────
        _bindEvents: function () {
            var self = this;

            // Abre modal
            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'aiwriter-open-btn') {
                    self._openModal();
                }
            });

            // Fecha modal
            document.getElementById('aiwriter-close').addEventListener('click', function () {
                self._closeModal();
            });
            document.getElementById('aiwriter-overlay').addEventListener('click', function (e) {
                if (e.target === this) self._closeModal();
            });

            // Toggle busca de referência
            document.querySelectorAll('input[name="ref_type"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    var searchBox = document.getElementById('aiwriter-ref-search');
                    searchBox.style.display = (this.value === 'custom') ? 'block' : 'none';
                });
            });

            // Busca produto de referência (debounce 400ms)
            document.getElementById('aiwriter-ref-input').addEventListener('input', function () {
                clearTimeout(self.refSearchTimer);
                var q = this.value.trim();
                if (q.length < 2) {
                    document.getElementById('aiwriter-ref-results').innerHTML = '';
                    return;
                }
                self.refSearchTimer = setTimeout(function () { self._searchRef(q); }, 400);
            });

            // Gera
            document.getElementById('aiwriter-generate-btn').addEventListener('click', function () {
                self._generate();
            });

            // Voltar ao passo 1
            document.getElementById('aiwriter-back-btn').addEventListener('click', function () {
                self._showStep(1);
            });

            // Aplica campos
            document.getElementById('aiwriter-apply-btn').addEventListener('click', function () {
                self._applyFields();
            });

            // Tabs do preview
            document.querySelectorAll('.aiwriter-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    self._switchTab(this.dataset.tab);
                });
            });
        },

        // ── Abre modal e pré-preenche o nome com o campo atual ────────────
        _openModal: function () {
            var nameField = document.getElementById('name');
            if (nameField && nameField.value.trim()) {
                document.getElementById('aiwriter-product-name').value = nameField.value.trim();
            }
            document.getElementById('aiwriter-overlay').style.display = 'flex';
            this._showStep(1);
        },

        _closeModal: function () {
            document.getElementById('aiwriter-overlay').style.display = 'none';
            document.getElementById('aiwriter-error').style.display = 'none';
        },

        // ── Busca produto de referência via AJAX ──────────────────────────
        _searchRef: function (q) {
            var self    = this;
            var results = document.getElementById('aiwriter-ref-results');
            results.innerHTML = '<div class="aiwriter-searching">Buscando...</div>';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', this.cfg.searchUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        self._renderRefResults(data);
                    } catch (e) {
                        results.innerHTML = '<div class="aiwriter-error-inline">Erro ao buscar.</div>';
                    }
                }
            };
            xhr.send('q=' + encodeURIComponent(q) + '&form_key=' + encodeURIComponent(this.cfg.formKey));
        },

        _renderRefResults: function (items) {
            var results = document.getElementById('aiwriter-ref-results');
            if (!items.length) {
                results.innerHTML = '<div class="aiwriter-no-results">Nenhum produto encontrado.</div>';
                return;
            }
            var html = '<ul class="aiwriter-ref-list">';
            items.forEach(function (item) {
                html += '<li data-id="' + item.id + '">' + item.name + ' <em>(ID: ' + item.id + ')</em></li>';
            });
            html += '</ul>';
            results.innerHTML = html;

            var self = this;
            results.querySelectorAll('li').forEach(function (li) {
                li.addEventListener('click', function () {
                    document.getElementById('aiwriter-ref-id').value = this.dataset.id;
                    document.getElementById('aiwriter-ref-selected').innerHTML =
                        '<span class="aiwriter-ref-badge">✔ ' + this.textContent + '</span>';
                    results.innerHTML = '';
                    document.getElementById('aiwriter-ref-input').value = '';
                });
            });
        },

        // ── Chama o controller para gerar ─────────────────────────────────
        _generate: function () {
            var self        = this;
            var productName = document.getElementById('aiwriter-product-name').value.trim();
            var refType     = document.querySelector('input[name="ref_type"]:checked').value;
            var refId       = (refType === 'custom')
                ? (parseInt(document.getElementById('aiwriter-ref-id').value) || 0)
                : this.cfg.defaultRefId;

            var errorBox = document.getElementById('aiwriter-error');
            errorBox.style.display = 'none';

            if (!productName) {
                errorBox.textContent = 'Informe o nome do produto.';
                errorBox.style.display = 'block';
                return;
            }
            if (refType === 'custom' && !refId) {
                errorBox.textContent = 'Selecione um produto de referência.';
                errorBox.style.display = 'block';
                return;
            }

            // Loading state
            document.getElementById('aiwriter-btn-label').style.display  = 'none';
            document.getElementById('aiwriter-btn-loading').style.display = 'inline';
            document.getElementById('aiwriter-generate-btn').disabled     = true;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', this.cfg.generateUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.timeout = 130000;

            xhr.onload = function () {
                self._resetBtn();
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        self.generated = resp.data;
                        self._fillPreview(resp.data);
                        self._showStep(2);
                    } else {
                        errorBox.textContent = resp.message || 'Erro desconhecido.';
                        errorBox.style.display = 'block';
                    }
                } catch (e) {
                    errorBox.textContent = 'Erro ao processar a resposta.';
                    errorBox.style.display = 'block';
                }
            };

            xhr.onerror = xhr.ontimeout = function () {
                self._resetBtn();
                errorBox.textContent = 'Timeout ou erro de conexão. Tente novamente.';
                errorBox.style.display = 'block';
            };

            xhr.send(
                'product_name=' + encodeURIComponent(productName) +
                '&reference_id=' + refId +
                '&form_key=' + encodeURIComponent(self.cfg.formKey)
            );
        },

        _resetBtn: function () {
            document.getElementById('aiwriter-btn-label').style.display  = 'inline';
            document.getElementById('aiwriter-btn-loading').style.display = 'none';
            document.getElementById('aiwriter-generate-btn').disabled     = false;
        },

        // ── Preenche preview com dados gerados ────────────────────────────
        _fillPreview: function (data) {
            document.getElementById('preview-name').textContent             = data.name || '';
            document.getElementById('preview-short').value                  = data.short_description || '';
            document.getElementById('preview-long').value                   = data.description || '';
            document.getElementById('preview-meta-title').textContent       = data.meta_title || '';
            document.getElementById('preview-meta-description').textContent = data.meta_description || '';
            document.getElementById('preview-meta-keywords').textContent    = data.meta_keywords || '';
            document.getElementById('preview-meta-page').textContent        = data.meta_page_description || '';
            this._switchTab('basic');
        },

        // ── Aplica campos no formulário do produto ────────────────────────
        _applyFields: function () {
            var data = this.generated;
            if (!data) return;

            // Pega valores editáveis do preview (o usuário pode ter ajustado)
            data.short_description = document.getElementById('preview-short').value;
            data.description       = document.getElementById('preview-long').value;

            this._setField('name',                    data.name);
            this._setField('meta_title',              data.meta_title);
            this._setField('meta_description',        data.meta_description);
            this._setField('meta_keyword',            data.meta_keywords);
            this._setField('meta_description',        data.meta_description);

            // Short description — textarea simples
            this._setField('short_description',       data.short_description);

            // Long description — pode ter editor WYSIWYG (tinyMCE)
            this._setEditorField('description',       data.description);

            // Meta page description (campo custom se existir)
            this._setField('custom_layout_update',    ''); // não tocar
            // Tenta campo meta_page_description se existir no tema
            this._setField('meta_page_description',   data.meta_page_description);

            this._closeModal();

            // Feedback visual
            var btn = document.getElementById('aiwriter-open-btn');
            var orig = btn.innerHTML;
            btn.innerHTML   = '✔ Aplicado!';
            btn.style.background = '#28a745';
            setTimeout(function () {
                btn.innerHTML        = orig;
                btn.style.background = '#6a4fc6';
            }, 3000);
        },

        // ── Helpers de campo ──────────────────────────────────────────────
        _setField: function (fieldName, value) {
            if (!value) return;
            var el = document.getElementById(fieldName);
            if (el) el.value = value;
        },

        _setEditorField: function (fieldName, value) {
            if (!value) return;
            // tinyMCE (editor WYSIWYG do Magento admin)
            if (typeof tinyMCE !== 'undefined') {
                var editor = tinyMCE.get(fieldName);
                if (editor) {
                    editor.setContent(value);
                    return;
                }
            }
            // Fallback para textarea simples
            var el = document.getElementById(fieldName);
            if (el) el.value = value;
        },

        // ── UI helpers ────────────────────────────────────────────────────
        _showStep: function (step) {
            document.getElementById('aiwriter-step-1').style.display = (step === 1) ? 'block' : 'none';
            document.getElementById('aiwriter-step-2').style.display = (step === 2) ? 'block' : 'none';
        },

        _switchTab: function (tab) {
            document.querySelectorAll('.aiwriter-tab').forEach(function (t) {
                t.classList.toggle('active', t.dataset.tab === tab);
            });
            document.querySelectorAll('.aiwriter-tab-content').forEach(function (c) {
                c.style.display = 'none';
            });
            var content = document.getElementById('tab-' + tab);
            if (content) content.style.display = 'block';
        }
    };

})();
