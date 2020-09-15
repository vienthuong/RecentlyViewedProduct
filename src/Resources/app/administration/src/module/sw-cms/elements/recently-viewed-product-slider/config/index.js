import template from './sw-cms-el-config-recently-viewed-product-slider.html.twig';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-recently-viewed-product-slider', {
    template,

    inject: ['repositoryFactory', 'systemConfigApiService'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            isLoading: false
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            if (this.element.isNew()) {
                this.isLoading = true;
                try {
                    const prefix = 'RecentlyViewedProduct.config';
                    const defaultConfig = await this.systemConfigApiService.getValues(prefix);

                    Object.keys(this.element.config).forEach(configKey => {
                        let config = prefix + '.' + configKey;

                        if (defaultConfig[config] || defaultConfig[config] === false) {
                            this.element.config[configKey].value = defaultConfig[config];
                        }
                    })
                } catch {
                    // nth
                } finally {
                    this.isLoading = false;
                }
            }

            this.initElementConfig('recently-viewed-product-slider');
        }
    }
});
