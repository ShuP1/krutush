<?php

namespace Krutush\Form;

class Input extends Element{
    public function text() : Input{
        $this->data['type'] = 'text';
        return $this;
    }

    public function phone() : Input{
        $this->data['type'] = 'tel';
        $this->data['pattern'] = "^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$";
        $this->data['phone'] = true;
        return $this;
    }

    public function email() : Input{
        $this->data['type'] = 'email';
        $this->data['email'] = true;
        return $this;
    }

    public function minlength(int $value) : Input{
        $this->data['minlength'] = $value;
        return $this;
    }

    public function maxlength(int $value) : Input{
        $this->data['maxlength'] = $value;
        return $this;
    }

    public function alpha(string $value = '') : Input{
        $this->data['type'] = 'text';
        $this->data['title'] = 'Alphabétique';
        $this->data['alpha'] = $value;
        return $this;
    }

    public function alphanum(string $value = '') : Input{
        $this->data['type'] = 'text';
        $this->data['title'] = 'Alphanumérique';
        $this->data['alphanum'] = $value;
        return $this;
    }

    public function regex(string $value) : Input{
        $this->data['type'] = 'text';
        $this->data['pattern'] = $value;
        $this->data['regex'] = $value;
        return $this;
    }

    public function title(string $value) : Input{
        $this->data['title'] = $value;
        return $this;
    }

    public function valid(mixde $data)/*: bool|string*/{
        $parent = parent::valid($data);

        if($parent !== true || !isset($data))
            return $parent;

        if(!empty($data)){
            if(isset($this->data['phone'])){
                if($this->data['phone'] == true && !preg_match("#^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$#", $data))
                    return 'incorrect';
            }else if(isset($this->data['email'])){
                if($this->data['email'] == true && !filter_var($data, FILTER_VALIDATE_EMAIL))
                    return 'incorrect';
            }else if(isset($this->data['minlength'])){
                if(strlen($data) < $this->data['minlength'])
                    return 'trop court';
            }else if(isset($this->data['maxlength'])){
                if(strlen($data) > $this->data['maxlength'])
                    return 'trop long';
            }else if(isset($this->data['alpha'])){
                if(!preg_match('#^[\p{L}'.$this->data['alpha'].']*$#', $data))
                    return 'non alphabétique';
            }else if(isset($this->data['alphanum'])){
                if(!preg_match('#^[\p{L}\p{N}'.$this->data['alphanum'].']*$#', $data))
                    return 'non alphanumérique';
            }else if(isset($this->data['regex'])){
                if(!preg_match('#'.$this->data['regex'].'#', $data))
                    return 'incorrect';
            }
        }
        return $parent;
    }

    public function html(string $more = '') : string{
        return '<input name="'.$this->data['name'].'" '.
        (isset($this->data['value']) ? 'value="'.$this->data['value'].'" ' : '').
        (isset($this->data['type']) ? 'type="'.$this->data['type'].'" ' : '').
        (isset($this->data['title']) ? 'title="'.$this->data['title'].'" ' : '').
        (isset($this->data['pattern']) ? 'pattern="'.$this->data['pattern'].'" ' : '').
        (isset($this->data['pattern']) ? 'pattern="'.$this->data['pattern'].'" ' : '').
        (isset($this->data['minlength']) ? 'minlength="'.$this->data['minlength'].'" ' : '').
        (isset($this->data['maxlength']) ? 'maxlength="'.$this->data['maxlength'].'" ' : '').
        (isset($this->data['required']) && $this->data['required'] == true ? 'required ' : '').
        $more.'>';
    }
}