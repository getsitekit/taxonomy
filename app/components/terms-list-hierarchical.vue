<script>
/*!
 * 层级分类选择列表组件（ES5 / CommonJS，兼容项目统一构建链）
 */
/*global _, UIkit*/
var Template = require('../templates/terms-list-hierarchical.html');
var RowTemplate = require('../templates/term-row-list-hierarchical.html');
var TermRawTemplate = require('../templates/term-raw.html');
var TermContentTemplate = require('../templates/term-content.html');

module.exports = {

    name: 'TermsListHierarchical',

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
                        if (vm.$options.name === 'TermsListHierarchical') {
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

    props: {
        'taxonomy': Object,
        'excluded': {type: Array, default: function () { return []; }},
        'limit': {type: Number, default: 1000},
        'allowAdd': {type: Boolean, default: true},
        'maxHeight': {type: Number, default: 0}
    },

    data: function () {
        return {
            edit: null,
            terms: false,
            search: '',
            config: {
                statuses: {},
                taxonomyName: this.taxonomy.name,
                filter: {status: 1, order: 'priority asc'},
                limit: this.limit
            },
            selected: [],
            index: {},
            tree: {},
            form: {}
        };
    },

    computed: {
        visibleIds: function () {
            if (this.search) {
                var search = this.search.toLowerCase();
                var ids = [];
                this.terms.forEach(function (term) {
                    if (term.title.toLowerCase().indexOf(search) > -1 ||
                        term.slug.toLowerCase().indexOf(search) > -1) {
                        ids.push(term.id);
                        if (term.parent_id && ids.indexOf(term.parent_id) === -1 && this.index[term.parent_id] !== undefined) {
                            var parent = this.index[term.parent_id];
                            ids.push(parent.id);
                            while (parent.parent_id && ids.indexOf(parent.parent_id) === -1 && this.index[parent.parent_id] !== undefined) {
                                ids.push(parent.parent_id);
                                parent = this.index[parent.parent_id];
                            }
                        }
                    }
                }, this);
                return ids;
            }
            return Object.keys(this.index).map(function (id) {
                return Number(id);
            });
        },
        overflowStyle: function () {
            if (this.maxHeight > 0) {
                return 'max-height: ' + this.maxHeight + 'px;';
            }
            return '';
        }
    },

    watch: {
        'terms': {
            handler: function () {
                this.tree = _(this.terms).sortBy('priority').groupBy('parent_id').value();
                this.index = {};
                this.terms.forEach(function (term) {
                    this.index[term.id] = term;
                }, this);
            },
            deep: true
        }
    },

    created: function () {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.load();
    },

    ready: function () {
        var self = this;
        UIkit.nestable(this.$els.nestable, {
            maxDepth: 20,
            group: 'taxonomy.terms.' + this.taxonomy.name
        }).on('change.uk.nestable', function (e, nestable, el, type) {
            if (type && type !== 'removed') {
                self.resource.save({id: 'updateOrder'}, {
                    taxonomyName: self.taxonomy.name,
                    terms: nestable.list()
                }).then(self.load, function () {
                    self.$notify('Reorder failed.', 'danger');
                });
            }
        });
    },

    methods: {
        active: function (term) {
            return this.selected.indexOf(term.id) !== -1;
        },
        visible: function (term) {
            return this.visibleIds.indexOf(term.id) !== -1;
        },
        disabled: function (term) {
            return this.excluded.indexOf(term.id) !== -1;
        },
        load: function () {
            var self = this;
            this.resource.query(this.config).then(function (res) {
                var data = res.data;
                self.$set('terms', data.terms);
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
        toggle: function (term) {
            if (!this.disabled(term)) {
                this[this.active(term) ? 'deselect' : 'select'](term);
            }
        },
        select: function (term) {
            if (this.selected.indexOf(term.id) === -1) {
                this.selected.push(term.id);
            }
        },
        deselect: function (term) {
            var idx = this.selected.indexOf(term.id);
            if (idx > -1) {
                this.selected.splice(idx, 1);
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
