<script>
/*!
 * 多术语选择组件（ES5 / CommonJS，兼容项目统一构建链）
 */
/*global _*/
var Template = require('../templates/input-terms-many.html');
var TermsListSingle = require('./terms-list-single.vue');
var TermsListHierarchical = require('./terms-list-hierarchical.vue');

module.exports = {

    name: 'InputTermsMany',

    template: Template,

    components: {
        'terms-list-single': TermsListSingle,
        'terms-list-hierarchical': TermsListHierarchical
    },

    props: {
        'taxonomyName': String,
        'item_id': Number,
        'allowAdd': {type: Boolean, default: true},
        'onSelect': {type: Function, default: _.noop},
        'onRemove': {type: Function, default: _.noop}
    },

    data: function () {
        return {
            taxonomy: false,
            selected: []
        };
    },

    computed: {
        excluded: function () {
            return this.selected.map(function (term) {
                return term.id;
            });
        }
    },

    created: function () {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.load();
    },

    methods: {
        load: function () {
            var self = this;
            return this.resource.query({id: 'item', taxonomyName: this.taxonomyName, item_id: this.item_id,}).then(function (res) {
                self.$set('taxonomy', res.data.taxonomy);
                self.$set('selected', res.data.terms);
            }, function () {
                self.$notify('Loading failed.', 'danger');
            });
        },
        save: function () {
            var self = this;
            return this.resource.save({id: 'item'}, {
                taxonomyName: this.taxonomyName,
                item_id: this.item_id,
                terms: this.selected
            }).then(function (res) {
                self.$set('selected', res.data.terms);
                self.$notify('Terms saved');
            }, function () {
                self.$notify('Loading failed.', 'danger');
            });
        },
        pick: function () {
            this.$refs.modal.open();
        },
        select: function () {
            var self = this;
            var selected = _.filter(this.$refs.termsList.getSelected(), function (term) {
                return _.find(self.selected, {id: term.id}) === undefined;
            });
            this.selected = this.selected.concat(selected);
            this.save().then(function () {
                self.$refs.modal.close();
                self.onSelect(self.selected);
            });
        },
        remove: function (item) {
            var self = this;
            this.selected.$remove(item);
            this.save().then(function () {
                self.onRemove(item);
            });
        },
        hasSelection: function () {
            return this.$refs.termsList.nrSelected() > 0;
        },
        isSelected: function (term) {
            return this.selected === term;
        }
    }

};
</script>
