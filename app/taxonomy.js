/*!
 * Taxonomy 前端入口（ES5 / CommonJS，兼容项目统一构建链）
 */
var TermsSingle = require('./components/terms-single.vue');
var TermsHierarchical = require('./components/terms-hierarchical.vue');
var InputTermsMany = require('./components/input-terms-many.vue');
var InputTermsOne = require('./components/input-terms-one.vue');

if (window.Vue) {

    window.Vue.component('terms-single', TermsSingle);
    window.Vue.component('terms-hierarchical', TermsHierarchical);
    window.Vue.component('input-terms-many', InputTermsMany);
    window.Vue.component('input-terms-one', InputTermsOne);

}
