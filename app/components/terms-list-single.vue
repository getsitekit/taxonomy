<script>
/*!
 * 平级分类选择列表组件（ES5 / CommonJS，兼容项目统一构建链）
 */
var Template = require('../templates/terms-list-single.html');
var TermRawTemplate = require('../templates/term-raw.html');
var TermContentTemplate = require('../templates/term-content.html');

module.exports = {

    name: 'TermsListSingle',

    template: Template,

    replace: false,

    partials: {
        'term-raw': TermRawTemplate,
        'term-content': TermContentTemplate
    },

    props: {
        'taxonomy': Object,
        'excluded': {type: Array, default: function () { return []; }},
        'allowAdd': {type: Boolean, default: true},
        'limit': {type: Number, default: 10}
    },

    data: function () {
        return {
            edit: null,
            terms: false,
            config: {
                statuses: {},
                taxonomyName: this.taxonomy.name,
                filter: {search: '', status: 1, order: 'title asc'},
                limit: this.limit,
                page: 0
            },
            pages: 0,
            count: '',
            selected: [],
            form: {}
        };
    },

    watch: {
        'config.filter': {
            handler: function () {
                if (this.config.page) {
                    this.config.page = 0;
                } else {
                    this.load();
                }
            },
            deep: true
        }
    },

    created: function () {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.$watch('config.page', this.load, {immediate: true});
    },

    methods: {
        active: function (term) {
            return this.selected.indexOf(term.id) !== -1;
        },
        disabled: function (term) {
            return this.excluded.indexOf(term.id) !== -1;
        },
        load: function () {
            var self = this;
            this.resource.query(this.config).then(function (res) {
                var data = res.data;
                self.$set('terms', data.terms);
                self.$set('pages', data.pages);
                self.$set('count', data.count);
                self.$set('selected', []);
                self.$set('config.statuses', data.statuses);
            }, function () {
                self.$notify('Loading failed.', 'danger');
            });
        },
        add: function () {
            this.$set('edit', {
                id: 0,
                title: '',
                slug: '',
                status: 1,
                type: this.taxonomy.type,
                link: '@todo',
                taxonomy: this.taxonomy.name
            });
            this.$refs.form.open();
        },
        saveTerm: function (term) {
            var self = this;
            this.resource.save({id: term.id}, {term: term})
                .then(function () {
                    self.load();
                }, function (res) {
                    self.$notify(res.data, 'danger');
                })
                .then(function () {
                    self.$refs.form.close();
                });
        },
        select: function (term) {
            if (this.selected.indexOf(term.id) === -1) {
                this.selected.push(term.id);
            }
        },
        nrSelected: function () {
            return this.selected.length;
        },
        getSelected: function () {
            var self = this;
            return this.terms.filter(function (term) {
                return self.selected.indexOf(term.id) !== -1;
            });
        }
    }

};
</script>
