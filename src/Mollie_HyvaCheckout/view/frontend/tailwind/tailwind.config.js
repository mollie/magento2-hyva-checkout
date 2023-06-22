module.exports = {
    content: [
        '../templates/**/*.phtml',
    ],
    options: {
        safelist: [
            'mollie-component',
            'mollie-card-component',
            'apple-pay-button',
        ],
    }
}

