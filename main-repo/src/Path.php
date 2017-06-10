<?php

namespace Krutush;

class Path {
    private static $paths = array();
    private static $strict = false;

    public static function sets(array $data){
        foreach($data as $key => $value){
            static::set($key, $value);
        }
    }

    public static function get(string $key): string{
        $key = strtoupper($key);
        switch($key){
            case 'ROOT':
                if(isset(static::$paths['ROOT']))
                    return static::$paths['ROOT'];

            case 'WWW':
            case 'WEB':
            case 'PUBLIC':
                if(isset(static::$paths['WWW']))
                    return static::$paths['WWW'];
                if(static::$strict == false)
                    return static::get('ROOT').'/public';

            case 'SRC':
                if(isset(static::$paths['SRC']))
                    return static::$paths['SRC'];
                if(static::$strict == false)
                    return static::get('ROOT').'/src';

            case 'CFG':
            case 'CONF':
            case 'CONFIG';
                if(isset(static::$paths['CFG']))
                    return static::$paths['CFG'];
                if(static::$strict == false)
                    return static::get('SRC').'/Config';

            case 'TPL':
            case 'TEMPLATE':
                if(isset(static::$paths['TPL']))
                    return static::$paths['TPL'];
                if(static::$strict == false)
                    return static::get('SRC').'/Template';

            case 'TMP':
            case 'CACHE':
                if(isset(static::$paths['TPL']))
                    return static::$paths['TPL'];
                if(static::$strict == false)
                    return static::get('SRC').'/Cache';
                
            default:
                trigger_error('Get Unknown key : '.$key);
                return '';
        }
    }

    public static function isset(string $key): bool{
        $key = strtoupper($key);
        switch($key){
            case 'ROOT':
                return isset(static::$paths['ROOT']);

            case 'WWW':
            case 'WEB':
            case 'PUBLIC':
                return isset(static::$paths['WWW']) ||
                    (static::$strict == false && static::isset('ROOT'));

            case 'SRC':
                return isset(static::$paths['SRC']) ||
                    (static::$strict == false && static::isset('ROOT'));

            case 'CFG':
            case 'CONF':
            case 'CONFIG';
                return isset(static::$paths['CFG']) ||
                    (static::$strict == false && static::isset('SRC'));

            case 'TPL':
            case 'TEMPLATE':
                return isset(static::$paths['TPL']) ||
                    (static::$strict == false && static::isset('SRC'));
                
            default:
                return false;
        }
    }

    public static function set(string $key, string $value): bool{
        $key = strtoupper($key);
        switch($key){
            case 'ROOT':
                static::$paths['ROOT'] = $value;
                return true;

            case 'WWW':
            case 'WEB':
            case 'PUBLIC':
                static::$paths['WWW'] = $value;
                return true;

            case 'SRC':
                static::$paths['SRC'] = $value;
                return true;

            case 'CFG':
            case 'CONF':
            case 'CONFIG';
                static::$paths['CFG'] = $value;
                return true;

            case 'TPL':
            case 'TEMPLATE':
                static::$paths['TPL'] = $value;
                return true;
                
            default:
                trigger_error('Set Unknown key : '.$key);
                return false;
        }
    }

    public static function setStrict(bool $value = true){
        static::$strict = $value;
    }
}