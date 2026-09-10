<?php $view->script('taxonomy-admin', 'taxonomy:app/bundle/taxonomy-admin.js', ['vue', 'taxonomy']) ?>

<div id="taxonomy" class="uk-form" v-cloak>

    <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
        <div>
            <h2 class="uk-margin-remove">{{ 'taxonomy.admin.heading' | trans }}</h2>
        </div>
    </div>

    <div v-for="taxonomy in taxonomies" class="uk-margin-large-bottom">

        <h3 class="uk-h2 uk-margin-bottom-remove">{{ taxonomy.label_plural | trans }}</h3>

        <component :is="taxonomy.type === 'hierarchical' ? 'terms-hierarchical' : 'terms-single'" :taxonomy-name="taxonomy.name"></component>

    </div>

</div>
