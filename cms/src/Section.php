<?php

    declare(strict_types=1);

    namespace BonsaiPress;
    class Section extends Template
    {
        protected array $sections = array();

        protected function preparse(): void
        {
            parent::preparse();
            preg_match_all('~<!--sebastiany\.net::(.*)::Start-->(.*)<!--sebastiany\.net::(\1)::End-->~UisS', $this->templates[$this->current], $secs);
            if (empty($secs[1]) or empty($secs[2]))
                throw new \RuntimeException("No section found: " . $this->current);

            $this->sections = array_merge($this->sections, array_combine(array_values($secs[1]), $secs[2]));

        }

        public function fetch(string $section): string
        {
            if (!isset($this->sections[$section])) {
                throw new \RuntimeException('No such Section : ' . $section);
            }
            $this->templates = array($this->sections[$section]);
            parent::replace();

            return $this->output;
        }

        public function section_exists(string $section): bool
        {
            return isset($this->sections[$section]);
        }

        public function get_sections(): array
        {
            return array_keys($this->sections);
        }

    }