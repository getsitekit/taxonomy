<?php

namespace Sitekit\Taxonomy\Controller;

use Sitekit\Application as App;
use Sitekit\User\Annotation\Access;

/**
 * 分类管理后台控制器
 */
#[Access(admin: true)]
class TaxonomyController
{
    /**
     * 分类管理后台首页：列出所有已注册的分类法（taxonomy）
     */
    #[Access('taxonomy: manage taxonomy')]
    public function indexAction()
    {
        return [
            '$view' => [
                'title' => __('taxonomy.admin.heading'),
                'name'  => 'taxonomy/admin/index.php'
            ],
            '$data' => [
                'taxonomies' => array_values(App::taxonomy()->all())
            ]
        ];
    }
}
