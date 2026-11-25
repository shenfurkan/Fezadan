<div class="scientific-tools">
    <button type="button" id="formulaTrigger" class="btn-secondary px-3 py-2 text-xs">Formül ekle</button>
    <button type="button" id="scientificImportTrigger" class="btn-secondary px-3 py-2 text-xs">LaTeX / Markdown aktar</button>
    <button type="button" id="scientificImportCancel" class="btn-secondary px-3 py-2 text-xs" hidden>Aktarımı iptal et</button>
    <input type="file" id="scientificInput" accept=".tex,.md,.markdown" data-worker-url="/assets/js/scientific-import-worker.js?v=<?= filemtime(ROOT . '/public_html/assets/js/scientific-import-worker.js') ?>" hidden>
</div>
<details class="scientific-help"><summary>Formül ve aktarım hakkında</summary><p>Formülü düzenlemek için üzerine tıklayın. Tek UTF-8 .tex veya .md dosyası aktarılabilir (512 KB). Görseller, harici dosyalar, özel paketler ve kaynakça otomatik aktarılmaz; sonucu önizlemede kontrol edin.</p></details>
<dialog id="formulaDialog" class="formula-dialog" aria-labelledby="formulaDialogTitle">
    <h2 id="formulaDialogTitle">Formül</h2>
    <label for="formulaSource">TeX kaynağı (ör. \sqrt[3]{x}; $ işaretleri olmadan)</label>
    <textarea id="formulaSource" rows="5" spellcheck="false" aria-describedby="formulaError"></textarea>
    <label><input id="formulaDisplay" type="checkbox"> Ayrı satırda göster</label>
    <div id="formulaPreview" class="formula-preview" aria-label="Formül önizlemesi"></div>
    <p id="formulaError" class="formula-error" role="status"></p>
    <div class="formula-actions">
        <button type="button" id="formulaDelete" class="btn-secondary px-3 py-2 text-xs" hidden>Formülü sil</button>
        <button type="button" id="formulaCancel" class="btn-secondary px-3 py-2 text-xs">Vazgeç</button>
        <button type="button" id="formulaSave" class="btn-primary px-3 py-2 text-xs">Ekle</button>
    </div>
</dialog>
