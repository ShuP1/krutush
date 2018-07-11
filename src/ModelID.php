<?php

namespace Krutush\Database;

use Krutush\Database\Request\Request;

class ModelID extends Model {
    /** @var string */
    public const ID = null;

    /** @var bool */
    public const ID_INCLUDE_TABLE = false;

    /**
     * Get table id name for static::ID or default one
     *
     * @see static::ID_INCLUDE_TABLE
     * @return string
     */
    public static function getID(): string{
        return static::ID ?? (static::ID_INCLUDE_TABLE ? static::getTable().static::WORD_SEPARATOR : '').'id';
    }

    public static function getFields(): array{
        if(empty(static::FIELDS))
            trigger_error('Any data fields');

        $fields = static::FIELDS;
        $id = static::getID();
        $fields[$id] = static::getFieldRawOptions($id);

        if(!isset(static::$complete_fields))
        static::$complete_fields = static::completeFields($fields);

        return static::$complete_fields;
    }

    /**
     * static::FIELDS[$field] with some checks
     *
     * @param string $field
     * @return array options
     */
    protected static function getFieldRawOptions(string $field): array{
        if($field == static::getID())
            return array_merge(
                [
                    'type' => 'int',
                    'primary' => true,
                    'not_null' => true,
                    'custom' => 'AUTO_INCREMENT' //TODO: be smart
                ],
                static::FIELDS[$field] ?? []
            );

        return parent::getFieldRawOptions($field);
    }

    /**
     * add values to static::getInsert and run it
     *
     * @param boolean $setID must update id value
     */
    public function insert(bool $setID = true){
        $res = parent::insert();
        if($setID)
            $this->{static::getID()} = Connection::get(static::getDatabase())->getLastInsertID();

        return $res;
    }

    /**
     * Use static:ID to get row
     *
     * @param mixed $id int is a good idea
     * @param array $loads run static::load
     * @return self|null
     */
    public static function find($id, array $loads = []): ?self{
        return static::first([$id], Request::toParam(static::getID()), $loads);
    }

   /**
    * Same as find but throw exception on null
    *
    * @param mixed $id int is a good idea
    * @param array $loads run static::load
    * @return self
    */
   public static function findOrFail($id, array $loads = []): self{
        return static::firstOrFail([$id], Request::toParam(static::getID()), $loads);
   }

   /**
    * Use static:ID to get rows
    *
    * @param array $ids array(int) is a good idea
    * @param array $loads run static::loads
    * @return array
    */
   public static function finds(array $ids, array $loads = []): array{
        return static::all($ids, Request::inParams(static::getID(), count($ids)), $loads);
   }

   /**
    * Same as find but throw exception on empty
    *
    * @param array $ids array(int) is a good idea
    * @param array $loads run static::loads
    * @return array
    */
   public static function findsOrFail(array $ids, array $loads = []): array{
        return static::allOrFail($ids, Request::inParams(static::getID(), count($ids)), $loads);
   }
}