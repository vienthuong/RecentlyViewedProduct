import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();
criteria.addAssociation('cover');

Shopware.Service('cmsService').registerCmsElement({
    name: 'recently-viewed-product-slider',
    label: 'sw-cms.elements.recentlyViewedProductSlider.label',
    component: 'sw-cms-el-recently-viewed-product-slider',
    configComponent: 'sw-cms-el-config-recently-viewed-product-slider',
    previewComponent: 'sw-cms-el-preview-recently-viewed-product-slider',
    removable: false,
    defaultConfig: {
        title: {
            source: 'static',
            value: 'Recently viewed products',
            required: true
        },
        displayMode: {
            source: 'static',
            value: 'cover'
        },
        navigation: {
            source: 'static',
            value: true
        },
        rotate: {
            source: 'static',
            value: false
        },
        border: {
            source: 'static',
            value: false
        },
        elMinWidth: {
            source: 'static',
            value: '250px'
        },
        verticalAlign: {
            source: 'static',
            value: null
        },
        includeAction: {
            source: 'static',
            value: false
        },
        includePrice: {
            source: 'static',
            value: true
        },
        includeRating: {
            source: 'static',
            value: false
        }
    }
});
