# Taxonomy（分类法）

Sitekit 的内容分类扩展。本代码为 alpha 版本，作者计划在完成后将该模块迁移到 Sitekit 命名空间。
欢迎开发者将其用于自己的扩展并反馈问题。

#### 待办事项（Todo）

- 为 canonical 链接设置默认术语
- 为多术语内容项设置 canonical 链接
- 优化模型的加载与缓存
- 术语及术语内内容的排序

## 注册一个分类法

默认会注册一个简单的标签（tag）分类。只需定义该分类法总览页所在的路由即可。
路由配置见[路由](#路由-routing)。

```php
$app->on('boot', function ($event, $app) {
    $app['taxonomy']->register('extension.item.tag', [
        'route' => '@extenstion/item/tag',
    ]);
});
```

你也可以定义更复杂的分类法，属性说明如下：

```php
$app['taxonomy']->register('extension.product.color', [
    'type' => 'single',              // 类型：single（平级）| hierarchical（层级）
    'label_single' => __('Color'),   // 单数名称
    'label_plural' => __('Colors'),  // 复数名称
    'route' => '@extension/product/color', // 该分类法前台的入口路由名
    'options' => [
        'term_type' => 'term-raw',   // 术语类型：term-raw（仅标题/别名）| term-content（可带图带正文）
    ],
]);
```

层级（hierarchical）分类要稍微复杂一些，需要你的扩展提供额外的路由与代码。

```php
$app['taxonomy']->register('extension.product.category', [
    'type' => 'hierarchical',
    'label_single' => __('Category'),
    'label_plural' => __('Categories'),
    'route' => '@extension/item/category',
    'options' => [
        'term_type' => 'term-content',
        'term_controller' => '\Vendor\Extension\Controller\ItemSiteController::categoryAction', // 分类归档页控制器
        'item_controller' => '\Vendor\Extension\Controller\ItemSiteController::itemAction',       // 分类内详情页控制器
        'item_resolver' => [
            'pattern' => '/{slug}',
            'resolver' => '\Vendor\Extension\ItemUrlResolver',
        ],
    ],
]);
```

更多细节参见[路由](#路由-routing)。

### 平级分类（Single Taxonomy）

术语只有一层，适用于属性、标签等场景。

### 层级分类（Hierarchical Taxonomy）

术语可以嵌套为多级树，适用于内容分类。

### 术语类型 term-raw / term-content

- `term-raw`：术语只包含标题与别名（slug）。
- `term-content`：术语额外支持图片与正文，解析后的 Markdown 内容可通过 JSON 属性 `content`
  或 `$term->getContent()` 获取。

未来应支持注册自定义术语类型。

## Taxonomy 的 JavaScript 接口

使用 Vue 应用时，请在你的视图中引入 taxonomy 的 JavaScript：

```php
$view->script('taxonomy');
```

或者把它加入依赖：

```php
<?php $view->script('item-edit', 'vendor/extension:app/bundle/item-edit.js', ['vue', 'taxonomy']) ?>
```

### 管理术语

扩展提供了一个 Vue 应用，可在任意视图中直接调用。

平级分类：

```html
<div is="terms-single" taxonomy-name="extension.product.color"></div>
```

层级分类：

```html
<div is="terms-hierarchical" taxonomy-name="extension.product.category"></div>
```

### 将术语关联到内容项

使用输入组件为内容项选择术语，只需传入内容项 ID 与分类法名称。

单项关联（每项一个术语）：

```html
<input-terms-one taxonomy-name="extension.product.color" :item_id="item.id"></input-terms-one>
```

多项关联（每项多个术语）：

```html
<input-terms-many taxonomy-name="extension.item.tag" :item_id="item.id"></input-terms-many>
```

## API

该模块提供一套 API 用于管理分类法与内容项的关联。

### 获取分类法对象

从管理器获取已注册的 Taxonomy 对象。

```php
$taxonomy = App::taxonomy('extension.item.tag');
```

或

```php
$taxonomy = $app['taxonomy']('extension.item.tag');
```

### 保存内容项的术语

使用 [Vue 组件](#将术语关联到内容项) 管理术语，或通过 API 自行处理：

```php
/**
 * @param int $item_id 内容项 ID
 * @param array $terms 术语数据数组
 */
$taxonomy = App::taxonomy('extension.item.tag')->saveTerms($item_id, $terms);
```

其中 `$terms` 是术语数据组成的关联数组，至少需包含 `id`。

### 获取全部分类法

获取所有已注册的分类法：

```php
/**
 * @return TaxonomyBase[]
 */
$taxonomy = App::taxonomy()->all();
```

### 获取全部术语

获取某分类法下所有已发布的术语：

```php
/**
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->terms();
```

### 获取术语树根节点

获取分类法术语树的根节点，可遍历该节点生成术语树。仅层级分类可用。

```php
/**
 * @param array $parameters ['start_level' => 1, 'depth' => PHP_INT_MAX, 'mode' => 'all']
 * @return Term|null
 */
$root_term = App::taxonomy('extension.product.category')->getRoot($parameters);
```

更多用法参见菜单文档。

### 获取内容项关联的术语

获取关联到某内容项的已发布术语：

```php
/**
 * @param int $item_id
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->itemTerms($item_id);
```

### 获取某术语关联的内容项 ID

通过术语的别名（slug）获取关联的内容项 ID：

```php
/**
 * @param string $slug
 * @return array 内容项 ID 数组
 */
$taxonomy = App::taxonomy('extension.item.tag')->itemIds($slug);
```

### 获取单个术语

直接使用 Term 模型按别名或 ID 查询：

```php
/**
 * @param string $slug
 * @return Term
 */
$term = App::taxonomy('extension.item.tag')->termBySlug($slug);

/**
 * @param int $id
 * @return Term
 */
$term = App::taxonomy('extension.item.tag')->termById($id);
```

### 获取术语路径

获取层级术语的祖先路径（包含术语自身）：

```php
/**
 * @param  $term
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->getPath($term);
```

### 获取术语子级

获取层级术语的直接子级：

```php
/**
 * @param Term $term
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->getChildren($term);
```

## 路由（Routing）

路由可以直接在你扩展的控制器中定义。

### 为单个术语绑定路由

为单个术语（如标签）绑定视图路由，请在控制器中注册 `/tag/{slug}` 路由，该控制器对应的路由名
即注册分类法时填写的 `route`。术语的别名（slug）会作为参数传给方法。

```php
/**
 * @Route("/tag/{slug}")
 * @return array
 */
public function tagAction ($slug) {

    if (!$taxonomy = App::taxonomy('game2art.tag.game2art_comic')) {
        return App::abort(400, 'Taxonomy not found');
    }

    $term = $taxonomy->termBySlug($slug);
    $item_ids = $taxonomy->itemIds($slug);

    $items = Item::query()->whereInSet('id', $item_ids)->orderBy('title')->get();

    return [
        '$view' => [
            'title' => __('Items with tag %tag%', ['%tag%' => $term->title]),
            'name' => 'vendor/extension/items_tag.php'
        ],
        'items' => array_values($items),
        'tag' => $term,
    ];
}
```

### 为层级术语绑定路由

以下是一个层级分类的参考示例：

```php
$app['taxonomy']->register('extension.product.category', [
    'type' => 'hierarchical',
    'label_single' => __('Category'),
    'label_plural' => __('Categories'),
    'route' => '@extension/item',
    'options' => [
        'term_type' => 'term-content',
        'term_controller' => '\Vendor\Extension\Controller\ItemSiteController::categoryAction',
        'item_controller' => '\Vendor\Extension\Controller\ItemSiteController::itemAction',
        'item_resolver' => [
            'pattern' => '/{slug}',
            'resolver' => '\Vendor\Extension\ItemUrlResolver',
        ],
    ],
]);
```

#### 术语路由

Taxonomy 会为层级分类中的每个术语生成独立路由，追加在注册的 `route` 之后并以 `/term` 结尾。
例如可能生成：`@extension/item/category/cat-2/term`、`@extension/item/category/cat-1/cat-2/term` 等。
链接保存在 `Term` 对象的 `link` 属性中，术语链接会挂载到 `options` 中配置的 `term_controller`。

在控制器中，术语 ID 会追加到方法参数中。

```php
/**
 * @Request({"filter": "array", "page":"int"})
 * @return array
 */
public function categoryAction ($filter = [], $page = null, $term_id = 0) {

    if (!$taxonomy = App::taxonomy('extension.product.category')) {
        return App::abort(400, 'Taxonomy not found');
    }

    $term = $taxonomy->termById($term_id);
    $item_ids = $taxonomy->itemIds($term->slug);

    // 应用过滤与分页

    $items = Item::query()->whereInSet('id', $item_ids)->orderBy('title')->get();

    return [
        '$view' => [
            'title' => __('Items in category %category%', ['%category%' => $term->title]),
            'name' => 'vendor/extension/items_category.php'
        ],
        'items' => array_values($items),
        'subcategories' => $taxonomy->getChildren($term),
        'category' => $term,
    ];
}
```

#### 术语内容项路由

属于术语的内容项路由会挂载到 `options` 中配置的 `item_controller`。
例如可能生成：`@extension/item/category/cat-2/item`、`@extension/item/category/cat-1/cat-2/item` 等，
可用这些链接为内容项生成 URL。

当提供了 URL 解析器（url resolver）时，会为所有内容项路由注册 URL 别名，从而生成内容项的
SEO 友好链接。示例中把 pattern 追加到链接并绑定到解析器。

`@extension/item/category/cat-2/item/{slug}` => `\Vendor\Extension\ItemUrlResolver`

在控制器中，术语 ID 会追加到方法参数中，你的 URL 解析器应负责把别名（slug）转换为 ID。

```php
/**
 * @Route("/{id}", name="/id")
 */
public function itemAction ($id = 0, $term_id = 0) {

    // 访问检查等
    $item = Item::find($id);

    if ($term_id) {
        $terms = App::taxonomy('extension.product.category')->itemTerms($id);
        if (!isset($terms[$term_id])) {
            App::abort(404, __('Item not found in category.'));
        }
    }

    return [
        '$view' => [
            'title' => __('Item details'),
            'name' => 'vendor/extension/item.php'
        ],
        'item' => $item,
        'category' => $terms[$term_id],
    ];
}
```
