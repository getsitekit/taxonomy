<?php

namespace Sitekit\Taxonomy\Model;

use Sitekit\Application as App;
use Sitekit\Database\ORM\Annotation\Deleting;
use Sitekit\Database\ORM\Annotation\Saving;
use Sitekit\Database\ORM\ModelTrait;

trait TermModelTrait
{
    use ModelTrait {
        find as modelFind;
    }

    protected static $terms;

    /**
     * Retrieves a term by its item_id.
     *
     * @param  mixed $id
     * @param  bool  $cached
     * @return Term
     */
    public static function find($id, $cached = false)
    {
        if (!$cached || !isset(self::$terms[$id])) {
            self::$terms[$id] = self::modelFind($id);
        }

        return self::$terms[$id];
    }

    /**
     * @param $taxonomy
     * @return Term[]
     */
    public static function byTaxonomy ($taxonomy) {
        return self::query()->where(compact('taxonomy'))
            ->orderBy('title', 'asc')
            ->where(['status'=> Term::STATUS_PUBLISHED])
            ->get();
    }

    /**

    /**
     * @param $slug
     * @return Term
     */
    public static function findBySlug ($taxonomy, $slug) {
        return self::query()->where(compact('taxonomy', 'slug'))->first();
    }

    /**
     * Retrieves all terms for item.
     * @param  string $taxonomy
     * @param  int    $item_id
     * @return Term[]
     */
    public static function fromItemId($taxonomy, $item_id)
    {
       return self::query()->select('t.*')->from('@taxonomy_term t')
           ->leftJoin('@taxonomy_term_item ti', 'ti.term_id = t.id')
           ->where([
               't.status' => Term::STATUS_PUBLISHED,
               't.taxonomy' => $taxonomy,
               'ti.item_id' => $item_id,
           ])
           ->orderBy('t.title')->get();
    }

    /**
     * 将孤儿子节点的父级重置为零。
     *
     * @return int
     */
    public static function fixOrphanedNodes()
    {
        if ($orphaned = self::getConnection()
            ->executeQuery(
                'SELECT n.id FROM @taxonomy_term n LEFT JOIN @taxonomy_term c ON c.id = n.parent_id AND c.taxonomy = n.taxonomy WHERE n.parent_id <> 0 AND c.id IS NULL'
            )->fetchFirstColumn()
        ) {
            return self::query()
                ->whereIn('id', $orphaned)
                ->update(['parent_id' => 0]);
        }

        return 0;
    }

    /**
     * 保存前处理：生成唯一 slug、更新路径与子节点路径、设置优先级
     */
    #[Saving]
    public static function saving($event, Term $term)
    {
        $db = self::getConnection();

        $i = 2;
        $id = $term->id;

        if (!$term->slug) {
            $term->slug = $term->title;
        }

        // A node cannot have itself as a parent
        if ($term->parent_id === $term->id) {
            $term->parent_id = 0;
        }

        // Ensure unique slug
        while (self::where(['slug = ?', 'parent_id= ?'], [$term->slug, $term->parent_id])->where(function ($query) use ($id) {
            if ($id) $query->where('id <> ?', [$id]);
        })->first()) {
            $term->slug = preg_replace('/-\d+$/', '', $term->slug).'-'.$i++;
        }

        // Update own path
        $path = '/'.$term->slug;
        if ($term->parent_id && $parent = Term::find($term->parent_id) and $parent->taxonomy == $term->taxonomy) {
            $path = $parent->path.$path;
        } else {
            // set Parent to 0, if old parent is not found
            $term->parent_id = 0;
        }

        // Update children's paths
        if ($id && $path != $term->path) {
            $db->executeUpdate(
                "UPDATE ".self::getMetadata()->getTable()
                ." SET path = REPLACE (CONCAT('//', path), ".$db->quote('//' . $term->path).", ".$db->quote($path).")"
                ." WHERE path LIKE ".$db->quote($term->path.'//%'));
        }

        $term->path = $path;
        if (is_array($term->getTaxonomy()->link) && !empty($term->getTaxonomy()->link['route'])) {
            $term->link = $term->getTaxonomy()->link['route'];
        } else {
            if ($term->getTaxonomy()->type == 'hierarchical') {
                $term->link = $term->getTaxonomy()->route . $path . '/term';
            } else {
                $term->link = $term->getTaxonomy()->route;
            }
        }

        // Set priority
        if (!$id) {
            $term->priority = 1 + (int) $db->executeQuery(
                'SELECT MAX(priority) FROM @taxonomy_term WHERE parent_id = ?',
                [$term->parent_id]
            )->fetchOne();
        }
    }

    /**
     * 删除前处理：将子节点的父级指向被删除节点的父级
     */
    #[Deleting]
    public static function deleting($event, Term $term      )
    {
        // Update children's parents
        foreach (self::where('parent_id = ?', [$term->id])->get() as $child) {
            $child->parent_id = $term->parent_id;
            $child->save();
        }
    }
}
