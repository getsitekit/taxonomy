/*!
 * Blog 文章编辑页 - 标签/分类（taxonomy）集成脚本
 * 依赖 blog 扩展的 post-edit 脚本（定义 window.Post）与 taxonomy 扩展脚本
 */
(function () {
    'use strict';

    // post-edit 脚本已定义 window.Post 后，向其注册标签/分类编辑区块
    if (!window.Post) {
        return;
    }

    window.Post.components['blog-tags'] = {

        section: {
            label: 'taxonomy.blog.tags_label',
            priority: 110
        },

        props: ['post', 'data', 'form'],

        template: '<div class="uk-form-horizontal">'
            + '<div class="uk-form-row">'
            + '<label for="form-blog-tags" class="uk-form-label">{{ \'taxonomy.blog.tags_label\' | trans }}</label>'
            + '<div class="uk-form-controls">'
            + '<input-terms-many taxonomy-name="blog.tag" :item_id="post.id"></input-terms-many>'
            + '</div>'
            + '</div>'
            + '</div>'

    };

    window.Post.components['blog-category'] = {

        section: {
            label: 'taxonomy.blog.category_label',
            priority: 105
        },

        props: ['post', 'data', 'form'],

        template: '<div class="uk-form-horizontal">'
            + '<div class="uk-form-row">'
            + '<label for="form-blog-category" class="uk-form-label">{{ \'taxonomy.blog.category_label\' | trans }}</label>'
            + '<div class="uk-form-controls">'
            + '<input-terms-one taxonomy-name="blog.category" :item_id="post.id"></input-terms-one>'
            + '</div>'
            + '</div>'
            + '</div>'

    };

})(window);
