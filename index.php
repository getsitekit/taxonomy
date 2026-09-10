<?php

return [

	'name' => 'taxonomy',

	'type' => 'extension',

	'main' => function ($app) {

        $app['taxonomy'] = function ($app) {
            return new Sitekit\Taxonomy\TaxonomyManager($app);
        };

    },

	'autoload' => [

		'Sitekit\\Taxonomy\\' => 'src'

	],
	'routes' => [

		'/taxonomy' => [
			'name' => '@taxonomy',
			'controller' => 'Sitekit\\Taxonomy\\Controller\\TaxonomyController'
		],

		'/api/taxonomy' => [
			'name' => '@taxonomy/api',
			'controller' => [
				'Sitekit\\Taxonomy\\Controller\\TaxonomyApiController'
			]
		]

	],

	'menu' => [

		'taxonomy' => [
			'label' => 'taxonomy.nav.title',
			'icon' => 'taxonomy:icon.svg',
			'url' => '@taxonomy',
			'active' => '@taxonomy*',
			'access' => 'taxonomy: manage taxonomy',
			'priority' => 115
		]

	],

	'resources' => [

		'taxonomy:' => ''

	],

	'config' => [
	],

    'permissions' => [

        'taxonomy: use taxonomy' => [
            'title' => 'taxonomy.permission.use_taxonomy'
        ],

        'taxonomy: manage taxonomy' => [
            'title' => 'taxonomy.permission.manage_taxonomy'
        ],

    ],

    'events' => [
        'boot' => function ($event, $app) {

            // 注册 blog 文章的标签（tag）分类
            $app['taxonomy']->register('blog.tag', [
                'type' => 'single',
                'label_single' => __('taxonomy.vocab.blog.tag.single'),
                'label_plural' => __('taxonomy.vocab.blog.tag.plural'),
                'route' => '@blog/tag',
            ]);

            // 注册 blog 文章的树形分类（category）
            $app['taxonomy']->register('blog.category', [
                'type' => 'hierarchical',
                'label_single' => __('taxonomy.vocab.blog.category.single'),
                'label_plural' => __('taxonomy.vocab.blog.category.plural'),
                'route' => '@blog/categories',
                'options' => [
                    'term_type' => 'term-content',
                    'term_controller' => '\Sitekit\Blog\Controller\SiteController::categoryAction',
                    'item_controller' => '\Sitekit\Blog\Controller\SiteController::postAction',
                    'item_resolver' => [
                        'pattern' => '/{slug}',
                        'resolver' => '\Sitekit\Blog\TaxonomyItemResolver',
                    ],
                ],
            ]);

            $app->subscribe(
                new \Sitekit\Taxonomy\Event\RouteListener()
            );
        },

        'view.scripts' => function ($event, $scripts) use ($app) {
            $scripts->register('taxonomy', 'taxonomy:app/bundle/taxonomy.js', ['vue', 'editor', 'uikit-nestable']);
        },
	]

];
