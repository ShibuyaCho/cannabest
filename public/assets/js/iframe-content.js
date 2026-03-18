document.addEventListener('DOMContentLoaded', function() {
    function sendMessageToParent(message) {
        window.parent.postMessage(message, '*');
    }

    document.body.addEventListener('click', function(event) {
        event.preventDefault();
        var element = event.target;
        var selector = generateSelector(element);
        var styles = getComputedStyle(element);

        sendMessageToParent({
            type: 'elementClicked',
            selector: selector,
            elementType: element.tagName.toLowerCase(),
            styles: {
                backgroundColor: styles.backgroundColor,
                color: styles.color,
                fontFamily: styles.fontFamily,
                fontSize: styles.fontSize,
                padding: styles.padding,
                margin: styles.margin
            }
        });
    });

    function generateSelector(element) {
        // This is a simple implementation. You might want to create a more robust selector generation.
        if (element.id) {
            return '#' + element.id;
        } else if (element.className) {
            return '.' + element.className.split(' ')[0];
        } else {
            return element.tagName.toLowerCase();
        }
    }

    window.addEventListener('message', function(event) {
        if (event.data.type === 'updateStyle') {
            var elements = document.querySelectorAll(event.data.selector);
            elements.forEach(function(element) {
                element.style[event.data.property] = event.data.value;
            });
        } else if (event.data.type === 'saveStyles') {
            // Implement saving logic here
            console.log('Saving styles');
        } else if (event.data.type === 'iframeLoaded') {
            console.log('Iframe loaded message received');
        }
    });

    sendMessageToParent({ type: 'previewLoaded' });
});
