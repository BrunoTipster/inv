<?php
/**
 * Funções de Validação de Dados
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:54:44 UTC
 */

class Validation {
    private static $instance = null;
    private $errors = [];

    /**
     * Construtor privado - Padrão Singleton
     */
    private function __construct() {}

    /**
     * Obtém instância única da classe
     * 
     * @return Validation
     */
    public static function getInstance(): Validation {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Valida dados do formulário
     * 
     * @param array $data Dados a serem validados
     * @param array $rules Regras de validação
     * @return bool
     */
    public function validate(array $data, array $rules): bool {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule => $parameter) {
                $method = 'validate' . ucfirst($rule);
                
                if (method_exists($this, $method)) {
                    if (!$this->$method($value, $parameter, $field)) {
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Retorna erros de validação
     * 
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Valida se campo é obrigatório
     */
    private function validateRequired($value, $parameter, $field): bool {
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = "O campo {$field} é obrigatório.";
            return false;
        }
        return true;
    }

    /**
     * Valida tamanho mínimo
     */
    private function validateMin($value, $parameter, $field): bool {
        if (strlen($value) < $parameter) {
            $this->errors[$field][] = "O campo {$field} deve ter no mínimo {$parameter} caracteres.";
            return false;
        }
        return true;
    }

    /**
     * Valida tamanho máximo
     */
    private function validateMax($value, $parameter, $field): bool {
        if (strlen($value) > $parameter) {
            $this->errors[$field][] = "O campo {$field} deve ter no máximo {$parameter} caracteres.";
            return false;
        }
        return true;
    }

    /**
     * Valida email
     */
    private function validateEmail($value, $parameter, $field): bool {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "O campo {$field} deve ser um email válido.";
            return false;
        }
        return true;
    }

    /**
     * Valida número
     */
    private function validateNumeric($value, $parameter, $field): bool {
        if (!is_numeric($value)) {
            $this->errors[$field][] = "O campo {$field} deve ser um número.";
            return false;
        }
        return true;
    }

    /**
     * Valida valor mínimo
     */
    private function validateMinValue($value, $parameter, $field): bool {
        if ($value < $parameter) {
            $this->errors[$field][] = "O campo {$field} deve ser maior ou igual a {$parameter}.";
            return false;
        }
        return true;
    }

    /**
     * Valida valor máximo
     */
    private function validateMaxValue($value, $parameter, $field): bool {
        if ($value > $parameter) {
            $this->errors[$field][] = "O campo {$field} deve ser menor ou igual a {$parameter}.";
            return false;
        }
        return true;
    }

    /**
     * Valida regex
     */
    private function validateRegex($value, $parameter, $field): bool {
        if (!preg_match($parameter, $value)) {
            $this->errors[$field][] = "O campo {$field} está em formato inválido.";
            return false;
        }
        return true;
    }

    /**
     * Valida igualdade com outro campo
     */
    private function validateEquals($value, $parameter, $field): bool {
        if ($value !== $_POST[$parameter]) {
            $this->errors[$field][] = "O campo {$field} deve ser igual ao campo {$parameter}.";
            return false;
        }
        return true;
    }

    /**
     * Valida tipo de arquivo
     */
    private function validateFileType($value, $parameter, $field): bool {
        if (!empty($_FILES[$field]['name'])) {
            $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $parameter)) {
                $this->errors[$field][] = "O tipo de arquivo não é permitido para o campo {$field}.";
                return false;
            }
        }
        return true;
    }

    /**
     * Valida tamanho de arquivo
     */
    private function validateFileSize($value, $parameter, $field): bool {
        if (!empty($_FILES[$field]['size'])) {
            if ($_FILES[$field]['size'] > $parameter) {
                $this->errors[$field][] = "O arquivo do campo {$field} excede o tamanho máximo permitido.";
                return false;
            }
        }
        return true;
    }

    /**
     * Valida CPF
     */
    private function validateCpf($value, $parameter, $field): bool {
        $cpf = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $this->errors[$field][] = "O CPF informado é inválido.";
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                $this->errors[$field][] = "O CPF informado é inválido.";
                return false;
            }
        }
        return true;
    }

    /**
     * Valida data
     */
    private function validateDate($value, $parameter, $field): bool {
        $date = DateTime::createFromFormat($parameter, $value);
        if (!$date || $date->format($parameter) !== $value) {
            $this->errors[$field][] = "O campo {$field} deve ser uma data válida no formato {$parameter}.";
            return false;
        }
        return true;
    }

    /**
     * Valida URL
     */
    private function validateUrl($value, $parameter, $field): bool {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "O campo {$field} deve ser uma URL válida.";
            return false;
        }
        return true;
    }

    /**
     * Valida formato de telefone
     */
    private function validatePhone($value, $parameter, $field): bool {
        $phone = preg_replace('/[^0-9]/', '', $value);
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            $this->errors[$field][] = "O campo {$field} deve ser um telefone válido.";
            return false;
        }
        return true;
    }

    /**
     * Valida valores permitidos
     */
    private function validateIn($value, $parameter, $field): bool {
        if (!in_array($value, $parameter)) {
            $this->errors[$field][] = "O valor selecionado para o campo {$field} é inválido.";
            return false;
        }
        return true;
    }
}