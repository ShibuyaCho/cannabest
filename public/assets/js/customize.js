$(document).ready(function() {
    const customizeOptions = $('#customize-options');
    const selectedElementInfo = $('#selected-element-info');
    let selectedElement = null;

    const customizableOptions = [
        { name: 'backgroundColor', label: 'Background Color', type: 'color' },
        { name: 'color', label: 'Text Color', type: 'color' },
        { name: 'fontFamily', label: 'Font Family', type: 'select', options: ['Arial', 'Helvetica', 'Times New Roman', 'Courier'] },
        { name: 'fontSize', label: 'Font Size', type: 'range', min: 12, max: 24, step: 1 },
        { name: 'padding', label: 'Padding', type: 'text' },
        { name: 'margin', label: 'Margin', type: 'text' }
    ];

    // Listen for messages from the iframe
    window.addEventListener('message', function(event) {
    console.log('Received message:', event.data);  // Add this line for debugging
    if (event.data.type === 'elementClicked') {
        selectedElement = event.data;
        updateSelectedElementInfo();
        populateCustomizationOptions();
    } else if (event.data.type === 'previewLoaded') {
        console.log('Preview loaded');
    }
});

    function updateSelectedElementInfo() {
            console.log('Updating selected element info:', selectedElement);
        selectedElementInfo.html(`
            <p><strong>Selected Element:</strong> ${selectedElement.selector}</p>
            <p><strong>Element Type:</strong> ${selectedElement.elementType}</p>
        `);
    }

    function populateCustomizationOptions() {
          console.log('Populating options for:', selectedElement);
           
        customizeOptions.empty();
        customizableOptions.forEach(option => {
            let inputHtml;
            let currentValue = selectedElement.styles[option.name] || '';

            if (option.type === 'color') {
                inputHtml = `<input type="color" id="${option.name}" name="${option.name}" value="${currentValue}">`;
            } else if (option.type === 'select') {
                inputHtml = `<select id="${option.name}" name="${option.name}">
                    ${option.options.map(opt => `<option value="${opt}" ${currentValue === opt ? 'selected' : ''}>${opt}</option>`).join('')}
                </select>`;
            } else if (option.type === 'range') {
                inputHtml = `<input type="range" id="${option.name}" name="${option.name}" min="${option.min}" max="${option.max}" step="${option.step}" value="${currentValue || option.min}">
                             <span id="${option.name}-value">${currentValue || option.min}</span>`;
            } else {
                inputHtml = `<input type="text" id="${option.name}" name="${option.name}" value="${currentValue}">`;
            }

            customizeOptions.append(`
                <div class="form-group">
                    <label for="${option.name}">${option.label}</label>
                    ${inputHtml}
                </div>
            `);
        });

        attachOptionChangeListeners();
    }

    function attachOptionChangeListeners() {
        customizeOptions.find('input, select').on('change', function() {
            const property = $(this).attr('name');
            const value = $(this).val();
            updateIframeStyle(selectedElement.selector, property, value);
            
            // Update the range value display if it's a range input
            if ($(this).attr('type') === 'range') {
                $(`#${property}-value`).text(value);
            }
        });
    }

    function updateIframeStyle(selector, property, value) {
        const iframe = document.getElementById('live-preview');
        iframe.contentWindow.postMessage({
            type: 'updateStyle',
            selector: selector,
            property: property,
            value: value
        }, '*');
    }

    // Handle save changes
    $('#save-changes').on('click', function() {
        const iframe = document.getElementById('live-preview');
        iframe.contentWindow.postMessage({
            type: 'saveStyles'
        }, '*');
    });
});
