<script>
/*!
 * 平级分类管理组件（ES5 / CommonJS，兼容项目统一构建链）
 */
/*global _*/
var Template = require('../templates/terms-single.html');
var TermRawTemplate = require('../templates/term-raw.html');
var TermContentTemplate = require('../templates/term-content.html');

module.exports = {

    name: 'TermsSingle',

    template: Template,

    partials: {
        'term-raw': TermRawTemplate,
        'term-content': TermContentTemplate
    },

    props: {
        'taxonomyName': String
    },

    replace: false,

    data: function () {
        return {
            edit: null,
            taxonomy: false,
            terms: false,
            config: {
                statuses: {},
                taxonomyName: this.taxonomyName,
                filter: this.$session.get('taxonomy.filter.' + this.taxonomyName, {search: '', order: 'title asc'}),
                page: 0
            },
            pages: 0,
            count: '',
            selected: [],
            form: {}
        };
    },

    computed: {
        statusOptions: function () {
            var options = _.map(this.config.statuses, function (status, id) {
                return {text: status, value: id};
            });
            return [{value: '', text: app.translator.trans('taxonomy.admin.show_all')}, {label: app.translator.trans('taxonomy.admin.filter_by'), options: options}];
        }
    },

    watch: {
        'config.filter': {
            handler: function (filter) {
                if (this.config.page) {
                    this.config.page = 0;
                } else {
                    this.load();
                }
                this.$session.set('taxonomy.filter.' + this.taxonomyName, filter);
            },
            deep: true
        }
    },

    created: function () {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.$watch('config.page', this.load, {immediate: true});
    },

    methods: {
        editTerm: function (term) {
            if (!term) {
                term = {
                    id: 0,
                    title: '',
                    slug: '',
                    status: 1,
                    type: this.taxonomy.type,
                    link: '@todo',
                    taxonomy: this.taxonomy.name
                };
            }
            this.$set('edit', _.merge({}, term));
            this.$refs.form.open();
        },
        saveTerm: function (term) {
            var self = this;
            this.save(term).then(function () {
                self.$notify('taxonomy.admin.term_saved');
                self.$refs.form.close();
            });
        },
        active: function (term) {
            return this.selected.indexOf(term.id) !== -1;
        },
        load: function () {
            var self = this;
            return this.resource.query(this.config).then(function (res) {
                var data = res.data;
                self.$set('taxonomy', data.taxonomy);
                self.$set('terms', data.terms);
                self.$set('pages', data.pages);
                self.$set('count', data.count);
                self.$set('config.statuses', data.statuses);
                self.$set('selected', []);
            }, function () {
                self.$notify('taxonomy.admin.loading_failed', 'danger');
            });
        },
        save: function (term) {
            var self = this;
            return this.resource.save({id: term.id}, {term: term})
                .then(function () {
                    return self.load();
                }, function (res) {
                    self.$notify(res.data, 'danger');
                });
        },
        getSelected: function () {
            var self = this;
            return this.terms.filter(function (term) {
                return self.selected.indexOf(term.id) !== -1;
            });
        },
        toggleStatus: function (term) {
            var self = this;
            term.status = term.status ? 0 : 1;
            this.save(term).then(function () {
                self.$notify('taxonomy.admin.term_saved');
            });
        },
        status: function (status) {
            var self = this;
            var terms = this.getSelected();
            terms.forEach(function (term) {
                term.status = status;
            });
            this.resource.save({id: 'bulk'}, {terms: terms}).then(function () {
                self.load();
                self.$notify('taxonomy.admin.terms_saved');
            }, function (res) {
                self.load();
                self.$notify(res.data, 'danger');
            });
        },
        getStatusText: function (term) {
            return this.config.statuses[term.status];
        },
        remove: function () {
            var self = this;
            this.resource.delete({id: 'bulk'}, {ids: this.selected}).then(function () {
                self.load();
                self.$notify('taxonomy.admin.terms_deleted');
            }, function (res) {
                self.load();
                self.$notify(res.data, 'danger');
            });
        }
    }

};
</script>
