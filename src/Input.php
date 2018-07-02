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

    public function checkbox(): Input{
        $this->data['type'] = 'checkbox';
        $this->data['checkbox'] = true;
        $this->data['value'] = $this->data['name'];
        return $this;
    }

    public function number(): Input{
        $this->data['type'] = 'number';
        $this->data['number'] = true;
        return $this;
    }

    public function date(): Input{
        $this->data['type'] = 'date';
        $this->data['date'] = true;
        return $this;
    }

    public function time(): Input{
        $this->data['type'] = 'time';
        $this->data['time'] = true;
        return $this;
    }

    public function min(string $value) : Input{
        $this->data['min'] = $value;
        return $this;
    }

    public function max(string $value) : Input{
        $this->data['max'] = $value;
        return $this;
    }

    public function password(bool $complexity = false): Input{
        $this->data['type'] = 'password';
        $this->data['password'] = true;
        if($complexity){
            $regex = '^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$';
            $this->data['pattern'] = $regex;
            $this->data['regex'] = $regex;
            $this->data['title'] = 'Mot de passe trop simple';
        }
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
        $this->data['alpha'] = $value; //TODO: add parttern
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

    public function valid($data)/*: bool|string*/{
        $parent = parent::valid($data);

        if($parent !== true || !isset($data))
            return $parent;

        if(!empty($data)){
            if(isset($this->data['phone']) && $this->data['phone'] == true && !preg_match("#^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$#", $data))
                return 'incorrect';

            if(isset($this->data['number']) && $this->data['number'] == true && !ctype_digit($data))
                return 'non numérique';

            if(isset($this->data['date']) && $this->data['date'] == true){
                $d = \DateTime::createFromFormat('Y-m-d', $data);
                if(!$d || $d->format('Y-m-d') != $data)
                    return 'incorrect';
            }
            if(isset($this->data['time']) && $this->data['time'] == true){
                $t = \DateTime::createFromFormat('H:i', $data);
                if(!$t || $t->format('H:i') != $data)
                    return 'incorrect';
            }
            if(isset($this->data['min']) && $data < $this->data['min'])
                return 'trop petit';

            if(isset($this->data['max']) && $data > $this->data['max'])
                return 'trop grand';

            if(isset($this->data['email']) && $this->data['email'] == true && !filter_var($data, FILTER_VALIDATE_EMAIL))
                return 'incorrect';

            if(isset($this->data['minlength']) && strlen($data) < $this->data['minlength'])
                return 'trop court';

            if(isset($this->data['maxlength']) && strlen($data) > $this->data['maxlength'])
                return 'trop long';

            if(isset($this->data['alpha']) && !preg_match('#^[\p{L}\p{M}'.$this->data['alpha'].']*$#', $data))
                return 'non alphabétique';

            if(isset($this->data['alphanum']) && !preg_match('#^[\p{L}\p{M}\p{N}'.$this->data['alphanum'].']*$#', $data))
                return 'non alphanumérique';

            if(isset($this->data['regex']) && !preg_match('#'.$this->data['regex'].'#', $data))
                return 'incorrect';

        }
        return $parent;
    }

    public function html(string $more = '') : string{
        return $this->htmlLabel().
        '<input name="'.$this->data['name'].'" '.
        'id="'.$this->getId().'" '.
        (isset($this->data['value']) && !(isset($this->data['password']) && $this->data['password'] == true) ? 'value="'.$this->data['value'].'" ' : '').
        (isset($this->data['type']) ? 'type="'.$this->data['type'].'" ' : '').
        (isset($this->data['title']) ? 'title="'.$this->data['title'].'" ' : '').
        (isset($this->data['pattern']) ? 'pattern="'.$this->data['pattern'].'" ' : '').
        (isset($this->data['min']) ? 'min="'.$this->data['min'].'" ' : '').
        (isset($this->data['max']) ? 'max="'.$this->data['max'].'" ' : '').
        (isset($this->data['minlength']) ? 'minlength="'.$this->data['minlength'].'" ' : '').
        (isset($this->data['maxlength']) ? 'maxlength="'.$this->data['maxlength'].'" ' : '').
        (isset($this->data['required']) && $this->data['required'] == true ? 'required ' : '').
        $more.'>';
    }
}