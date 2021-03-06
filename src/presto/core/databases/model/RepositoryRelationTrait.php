<?php
namespace Presto\Core\Databases\Model;

use Presto\Core\Databases\Model\Manages\RelationManage;
use Presto\Core\Databases\Model\Manages\Relation;
use Presto\Core\Databases\Model\Manages\SliceManage;
use Presto\Core\Databases\Model\Manages\ScopeManage;
use Presto\Core\Utilities\Arrayer;
use Presto\Core\Utilities\Collection;

/**
 * @property array $slices
 * @property array $scopes
 * @property array $relations
 */
trait RepositoryRelationTrait
{
    /** @var \Presto\Core\Databases\Model\Manages\RelationManage */
    protected  $relationManage;

    /** @var \Presto\Core\Databases\Model\Manages\SliceManage */
    protected  $sliceManage;

    /** @var \Presto\Core\Databases\Model\Manages\ScopeManage */
    protected  $scopeManage;


    /**
     * 全リレーションのロード
     * @param array|mixed $rows
     * @return Collection|Model[]|array|mixed
     */
    public function loadRelations(\ArrayAccess $rows, int $recursion=0)
    {
        $this->relationManage = new RelationManage($this->relations);
        $this->sliceManage = new SliceManage($this->slices);
        $this->scopeManage = new ScopeManage($this->scopes);

        // リレーション定義がない場合、そのまま返す
        if( ! $this->relationManage->isUseRelations() )
        {
            return $rows;
        }

        // 全リレーションをループしながら、ロードする
        foreach ($this->relationManage->all() as $relation)
        {
            $rows = $this->loadRelation($rows, $relation, $recursion);
        }

        return $rows;
    }


    /**
     * 指定リレーションのロード
     * @param array $rows
     * @param Relation $relation
     * @return array|mixed
     */
    public function loadRelation(\ArrayAccess $rows, Relation $relation, int $recursion=0)
    {
        // 子テーブルの検索条件
        $cond = $this->foreignCondition($rows, $relation);

        // 子テーブルの検索
        $foreigns = $relation->getRepository()->find(["condition"=>$cond], $recursion);

        // 子テーブルを親に代入
        $rows = Arrayer::instance()->mapping($rows, $foreigns, $relation->join, $relation->type, TRUE);

        return $rows;
    }


    /**
     * 子テーブルの対象項目の値一覧の抽出
     * @param array $rows
     * @param Relation $relation
     * @return array
     */
    protected function foreignCondition(\ArrayAccess $rows, Relation $relation)
    {
        $cond = [];

        foreach ($rows as $no=>$row)
        {
            // where指定の場合は、親をフィルターする
            if(! $relation->isWhereTarget($row))
            {
                continue;
            }

            // join条件の生成
            foreach ($relation->join as $foreign_name=>$mapping)
            {
                if(empty($mapping))
                {
                    break;
                }

                $sub_cond = [];
                foreach ($mapping as $parent_field=>$children_field)
                {
                    $sub_cond[$children_field] = $row[$parent_field];
                }

                // TODO 同じ条件が重複されないように
                $cond["or"][] = $sub_cond;
            }
        }

        return $cond;
    }


}