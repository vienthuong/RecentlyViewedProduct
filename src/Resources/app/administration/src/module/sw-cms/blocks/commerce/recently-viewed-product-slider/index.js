import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'recently-viewed-product-slider',
    label: 'sw-cms.blocks.commerce.recentlyViewedProductSlider.label',
    category: 'commerce',
    component: 'sw-cms-block-recently-viewed-product-slider',
    previewComponent: 'sw-cms-preview-recently-viewed-product-slider',
    defaultConfig: {
        marginBottom: '10px',
        marginTop: '10px',
        marginLeft: '10px',
        marginRight: '10px',
        sizingMode: 'boxed'
    },
    slots: {
        recentlyViewedProductSlider: 'recently-viewed-product-slider'
    }
});
