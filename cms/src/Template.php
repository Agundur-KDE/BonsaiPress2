<?php

    declare(strict_types=1);

    namespace BonsaiPress;

    class Template
    {
        protected array $_templates = [];
        protected array $_placeholder = [];

        protected string  $current	= '';
        protected string $output = '';
        public bool $keep_unassigned = false;

        public function read(string $template): void
        {
            if (isset($this->_templates[$template])) {
                return;
            }
            $this->current = $template;
            $this->preparse();
        }

        protected function preparse(): void
        {
            if (!file_exists($this->current) || !is_readable($this->current)) {
                throw new \RuntimeException("Template not found or not readable: " . $this->current);
            }

            $this->_templates[$this->current] = file_get_contents($this->current);

            preg_match_all('~{[^\s].*}~UisS', $this->_templates[$this->current], $matches);

            if ($this->keep_unassigned == true) {
                $this->_placeholder = array_combine(array_values($matches[0]), array_values($matches[0]));
            } else {
                $this->_placeholder = array_merge($this->_placeholder, array_combine(($matches[0]), array_fill(0, count($matches[0]), '')));
            }
        }
        public function render(): string
        {
            $this->replace();
            return $this->output;
        }

        protected function replace(): void
        {
            $this->output = '';
            foreach ($this->_templates as $key => &$content) {
                $this->output .= str_replace(array_keys($this->_placeholder), array_values($this->_placeholder), $content);
            }
        }
        public function assign(string $holder,string $value): void{
            $this->_placeholder['{' . $holder . '}'] = $value;
        }

        public function add(string $holder, string $value): void{
            $this->_placeholder['{' . $holder . '}'] .= $value;
        }
    }