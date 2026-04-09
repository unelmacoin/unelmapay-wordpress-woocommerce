(function () {
    if (!window.wc || !window.wc.wcBlocksRegistry || !window.wp || !window.wp.element) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getPaymentMethodData } = window.wc.wcSettings;
    const { createElement } = window.wp.element;

    const settings = getPaymentMethodData('unelmapay', {});
    const labelText = settings.title || 'UnelmaPay';
    const descText = settings.description || 'Pay securely via UnelmaPay';

    const Content = createElement('div', null, descText);

    registerPaymentMethod({
        name: 'unelmapay',
        paymentMethodId: 'unelmapay',
        label: createElement('span', null, labelText),
        content: Content,
        edit: Content,
        ariaLabel: labelText,
        canMakePayment: function () {
            return true;
        },
        supports: {
            features: ['products'],
        },
    });
})();