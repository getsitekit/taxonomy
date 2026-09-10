/*!
 * 分类管理后台入口（webpack entry 源码，构建输出到 app/bundle/taxonomy-admin.js）
 * 依赖 taxonomy 扩展脚本（全局组件 terms-single 等）与 vue
 */
(function () {
    'use strict';

    // 根实例：数据来自后端 $data（已注册的分类法列表）
    window.TaxonomyAdmin = {
        name: 'taxonomy',
        el: '#taxonomy',
        data: function () {
            return _.merge({
                taxonomies: []
            }, window.$data);
        }
    };

    if (window.Vue) {
        Vue.ready(window.TaxonomyAdmin);
    }

})(window);
