<?php

namespace Krutush\Form;

class Select extends Element{
    public function option(string $value, string $text = null, string $more = '') : Select{
        $this->data['options'][] = array(
            'value' => $value,
            'text' => isset($text) ? $text : $value,
            'more' => $more
        );
        return $this;
    }

    public function options(array $options) : Select{
        foreach($options as $option){
            if(is_string($option)){
                $this->option($option);
                continue;
            }

            $this->data['options'][] = $option; //TODO convert to $this->option
        }
        return $this;
    }

    public function valid(mixed $data)/*: bool|string*/{
        $parent = parent::valid($data);
        if($parent !== true || !isset($data))
            return $parent;

        foreach($this->data['options'] as $option){
            if($option['value'] == $data)
                return $parent;
        }
        return 'incorrect';
    }

    public function html(string $more = '') : string{
        $html = '<select name="'.$this->data['name'].'" '.
        (isset($this->data['required']) && $this->data['required'] == true ? 'required ' : '').
        $more.'><option disabled '.(isset($this->data['value']) ? 'selected ' : '').'value style="display:none"> --- </option>';
        foreach($this->data['options'] as $option){
            $html .= '<option value="'.$option['value'].'" '.((isset($this->data['value']) && $this->data['value'] == $option['value']) ? 'selected="selected" ' : '' ).$option['more'].'>'.$option['text'].'</option>';
        }
        return $html.'</select>';
    }
}