<div class="type-wrapper mb-3">
    <select name="type" class="form-select js-type-select" data-original="{{ $type }}">
        <option value="lxc" {{ $type === 'lxc' ? 'selected' : '' }}>LXC</option>
        <option value="vps" {{ $type === 'vps' ? 'selected' : '' }}>VPS</option>
        <option value="app" {{ $type === 'app' ? 'selected' : '' }}>App</option>
    </select>
</div>



































<script>
    document.addEventListener('change', function (e) {
        const sel = e.target;
        if (!sel.matches('select.js-type-select')) return;

        if (!sel.dataset.original) sel.dataset.original = sel.value;
        const original = sel.dataset.original;

        let hidden = sel.parentNode.querySelector('input[name="type_new"]');

        if (sel.value !== original) {
            if (hidden) {
                hidden.value = sel.value;
            } else {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'type_new';
                hidden.value = sel.value;
                sel.parentNode.insertBefore(hidden, sel.nextSibling);
            }
        } else {
            if (hidden) hidden.remove();
        }
    });
</script>


