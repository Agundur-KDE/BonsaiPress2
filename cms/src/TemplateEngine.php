 <?php

declare(strict_types=1);


 interface TemplateEngine {
      public function read(string $template): bool;
      public function assign(string $holder, string $value): void;
      public function render(): string;
  }

