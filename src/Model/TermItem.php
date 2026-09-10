<?php

namespace Sitekit\Taxonomy\Model;

use Sitekit\Application as App;
use Sitekit\Database\ORM\Annotation\Column;
use Sitekit\Database\ORM\Annotation\Entity;
use Sitekit\Database\ORM\Annotation\Id;
use Sitekit\Database\ORM\ModelTrait;
use Sitekit\Database\Query\QueryBuilder;

#[Entity(tableClass: '@taxonomy_term_item')]
class TermItem implements \JsonSerializable
{
    use ModelTrait;

    #[Column(type: 'integer')]
    #[Id]
    public $id;

    #[Column(type: 'integer')]
    public $item_id;

    #[Column(type: 'integer')]
    public $term_id;

    #[Column(type: 'integer')]
    public $term_ordering = 0;

    /**
     * 根据 slug 获取术语关联的所有内容项 ID。
     * @param  string $taxonomy
     * @param  string $slug
     * @return array
     */
    public static function itemIdsFromSlug($taxonomy, $slug)
    {
        // 直接使用 Sitekit 查询构造器返回关联数组，避免 ORM 单列 hydrate 问题
        $rows = (new QueryBuilder(self::getConnection()))
            ->select('ti.item_id')
            ->from('@taxonomy_term_item ti')
            ->leftJoin('@taxonomy_term t', 't.id = ti.term_id')
            ->where([
                't.taxonomy' => $taxonomy,
                't.slug' => $slug,
            ])
            ->orderBy('t.title')
            ->get();

        return array_map(function ($row) {
            return (int) $row['item_id'];
        }, $rows);
    }

    //todo term ordering

}
