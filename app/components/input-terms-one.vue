<script>
/*!
 * 单选术语组件（ES5 / CommonJS，兼容项目统一构建链）
 */
/*global _*/
var Template = require('../templates/input-terms-one.html');
var TermsListSingle = require('./terms-list-single.vue');
var TermsListHierarchical = require('./terms-list-hierarchical.vue');

module.exports = {

    name: 'InputTermsOne',

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
            selected: false
        };
    },

    computed: {
        excluded: function () {
            return this.selected ? [this.selected.id] : [];
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
                self.$set('selected', res.data.terms[0]);
            }, function () {
                self.$notify('Loading failed.', 'danger');
            });
        },
        save: function () {
            var self = this;
            return this.resource.save({id: 'item'}, {
                taxonomyName: this.taxonomyName,
                item_id: this.item_id,
                terms: [this.selected]
            }).then(function (res) {
                self.$set('selected', res.data.terms[0]);
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
            this.selected = _.first(this.$refs.termsList.getSelected());
            this.save().then(function () {
                self.$refs.modal.close();
                self.onSelect(self.selected);
            });
        },
        remove: function () {
            var self = this;
            this.selected = false;
            this.save().then(function () {
                self.onRemove();
            });
        },
        hasSelection: function () {
            return this.$refs.termsList.nrSelected() === 1;
        },
        isSelected: function (term) {
            return this.selected === term;
        }
    }

};
</script>
