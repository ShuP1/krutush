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

    public function other(Input $input, string $text, string $more = ''): Select{
        $input->rename($this->name());
        $this->data['other'] = $input;
        $this->data['other.text'] = $text;
        $this->data['other.more'] = $more;
        return $this;
    }


    public function valid($data)/*: bool|string*/{
        $parent = parent::valid($data);
        if($parent !== true || !isset($data))
            return $parent;

        foreach($this->data['options'] as $option){
            if($option['value'] == $data)
                return $parent;
        }
        if(isset($this->data['other'])){
            $input = $this->data['other'];
            return $input->valid($data);
        }

        return 'incorrect';
    }

    public function html(string $more = ''): string{
        $selected = false;
        $options = '<option disabled '.(isset($this->data['value']) ? '' : 'selected ').'value style="display:none"> --- </option>';
        foreach($this->data['options'] as $option){
            $options .= '<option value="'.$option['value'].'" ';
            if(isset($this->data['value']) && $this->data['value'] == $option['value']){
                $options .= 'selected="selected" ';
                $selected = true;
            }
            $options .= $option['more'].'>'.$option['text'].'</option>';
        }

        $html = $this->htmlLabel().
        '<select name="'.$this->data['name'].'" '.
        'id="'.$this->getId().'" ';
        $inputmore = '';
        if(isset($this->data['other.text'])){
            $options .= '<option value="'.$this->data['other.text'].'" '.(isset($this->data['value']) && $selected == false ? 'selected="selected" ' : '').'>'.$this->data['other.text'].'</option>';
            //script in From->start()
            $inputmore .= 'class="SelectOther" onchange="SelectOther(this,\''.$this->data['other.text'].'\')" ';
            $html .= 'class="SelectOther" onchange="SelectOther(this,\''.$this->data['other.text'].'\')" ';
            if(isset($this->data['value']) && $selected == false){
                $html .= 'disabled style="display: none;" ';
                $this->data['other']->value($this->data['value']);
            }else{
                $inputmore .= 'disabled style="display: none;" ';
            }
        }
        if(isset($this->data['required']) && $this->data['required'] == true){
            $html .= 'required ';
            $inputmore .= 'required ';
        }
        $html .= $more.'>';
        $html .= $options;
        $html .= '</select>';
        if(isset($this->data['other'])){
            $html .= $this->data['other']->html($inputmore.$this->data['other.more']);
        }
        return $html;
    }
}