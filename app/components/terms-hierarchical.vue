<script>
/*!
 * 层级分类管理组件（ES5 / CommonJS，兼容项目统一构建链）
 */
/*global _, UIkit*/
var Template = require('../templates/terms-hierarchical.html');
var RowTemplate = require('../templates/term-row-hierarchical.html');
var TermRawTemplate = require('../templates/term-raw.html');
var TermContentTemplate = require('../templates/term-content.html');

module.exports = {

    name: 'TermsHierarchical',

    template: Template,

    partials: {
        'term-raw': TermRawTemplate,
        'term-content': TermContentTemplate
    },

    components: {
        'term-row': {
            name: 'TermRow',
            props: {'term': Object, 'tree': Object},
            computed: {
                rootVm: function () {
                    var root = false, vm = this;
                    do {
                        if (vm.$options.name === 'TermsHierarchical') {
                            root = vm;
                        }
                        vm = vm.$parent;
                    } while (vm && !root);
                    return root;
                }
            },
            methods: {
                toggleStatus: function () {
                    this.term.status = this.term.status === 1 ? 0 : 1;
                    this.rootVm.save(this.term);
                }
            },
            template: RowTemplate
        }
    },

    props: {'taxonomyName': String},

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
                page: 0,
                limit: 1000
            },
            pages: 0,
            count: '',
            selected: [],
            tree: [],
            form: {}
        };
    },

    computed: {
        statusOptions: function () {
            var options = _.map(this.config.statuses, function (text, value) {
                return {text: text, value: value};
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
        },
        'terms': {
            handler: function () {
                this.$set('tree', _(this.terms).sortBy('priority').groupBy('parent_id').value());
            },
            deep: true
        }
    },

    created: function () {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.$watch('config.page', this.load, {immediate: true});
    },

    ready: function () {
        var self = this;
        UIkit.nestable(this.$els.nestable, {
            maxDepth: 20,
            group: 'taxonomy.terms.' + this.taxonomyName
        }).on('change.uk.nestable', function (e, nestable, el, type) {
            if (type && type !== 'removed') {
                self.resource.save({id: 'updateOrder'}, {
                    taxonomyName: self.taxonomyName,
                    terms: nestable.list()
                }).then(self.load, function () {
                    self.$notify('taxonomy.admin.reorder_failed', 'danger');
                });
            }
        });
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
        status: function (status) {
            var self = this;
            var terms = this.getSelected();
            terms.forEach(function (term) {
                term.status = status;
            });
            this.resource.save({id: 'bulk'}, {terms: terms}).then(function () {
                self.load();
                self.$notify('taxonomy.admin.terms_saved');
            });
        },
        getSelected: function () {
            return this.terms.filter(function (term) {
                return this.isSelected(term);
            }, this);
        },
        isSelected: function (term, children) {
            var self = this;
            if (_.isArray(term)) {
                return _.every(term, function (t) {
                    return self.isSelected(t, children);
                });
            }
            return this.selected.indexOf(term.id) !== -1 && (!children ||
                !this.tree[term.id] ||
                this.isSelected(this.tree[term.id], true));
        },
        toggleSelect: function (term) {
            var index = this.selected.indexOf(term.id);
            if (index === -1) {
                this.selected.push(term.id);
            } else {
                this.selected.splice(index, 1);
            }
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
