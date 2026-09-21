var form = document.getElementById('product-form');
if (!form) {
    // not on the product page
} else {
    var priceBox = document.getElementById('price');
    var priceNote = document.getElementById('price-note');
    var preview = document.getElementById('card-preview');
    var sizeLabel = document.getElementById('preview-size-label');
    var paperLabel = document.getElementById('preview-paper-label');
    var fileStatus = document.getElementById('file-status');

    var sampleLabel = document.getElementById('preview-sample-label');
    var sampleNames = {
        classic: 'Classic',
        navy: 'Navy',
        minimal: 'Minimal',
        stripe: 'Gold stripe'
    };

    function checkedVal(name) {
        var el = form.querySelector('input[name="' + name + '"]:checked');
        if (el) {
            return el.value;
        }
        el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    }

    function updateCard() {
        var size = checkedVal('size');
        var paper = checkedVal('paper');
        var sample = checkedVal('sample') || 'classic';

        preview.className = 'card-preview sample-' + sample;
        preview.className += (size == 'Square') ? ' size-square' : ' size-standard';
        preview.className += (paper == 'Glossy') ? ' finish-glossy' : ' finish-matte';

        sizeLabel.innerHTML = (size == 'Square') ? 'Square · 2.5" × 2.5"' : 'Standard · 3.5" × 2"';
        paperLabel.innerHTML = paper;
        if (sampleLabel) {
            sampleLabel.innerHTML = sampleNames[sample] || sample;
        }
    }

    function updatePrice() {
        var qty = checkedVal('quantity');
        var paper = checkedVal('paper');
        var matteLabel = document.getElementById('paper-price-matte');
        var glossyLabel = document.getElementById('paper-price-glossy');

        fetch('price.php?quantity=' + encodeURIComponent(qty) + '&paper=' + encodeURIComponent(paper))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) {
                    priceBox.innerHTML = '—';
                    priceNote.innerHTML = data.message;
                    return;
                }
                priceBox.innerHTML = data.formatted;
                priceNote.innerHTML = data.note;
            });

        fetch('price.php?quantity=' + encodeURIComponent(qty) + '&paper=Matte')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok && matteLabel) {
                    matteLabel.innerHTML = data.formatted;
                }
            });

        fetch('price.php?quantity=' + encodeURIComponent(qty) + '&paper=Glossy')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok && glossyLabel) {
                    glossyLabel.innerHTML = data.formatted;
                }
            });
    }

    form.addEventListener('change', function(e) {
        if (e.target.name == 'artwork' && e.target.files.length) {
            fileStatus.style.display = 'block';
            fileStatus.innerHTML = e.target.files[0].name;
        }

        updateCard();

        if (e.target.name == 'quantity' || e.target.name == 'paper') {
            updatePrice();
        }
    });

    var sampleRow = document.getElementById('sample-row');
    if (sampleRow) {
        sampleRow.addEventListener('change', updateCard);
    }
}
