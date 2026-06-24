---
name: scss-mastery
description: Expert SCSS/SASS development with mastery of nesting, parent selectors (&), child/descendant selectors, BEM methodology, and proper class hierarchy. Use when creating or editing SCSS/SASS files, styling with nested selectors, implementing component-based CSS architecture, or when deep understanding of SCSS nesting patterns, mixins, and modern CSS preprocessing is required.
---

# SCSS Mastery

This skill provides expert guidance for writing professional, maintainable SCSS with perfect nesting patterns and class hierarchies.

## Quick Reference

### Basic Nesting Structure
```scss
.component {
  property: value;
  
  &__element {
    // Child element (BEM)
  }
  
  &--modifier {
    // Variant (BEM)
  }
  
  &:hover,
  &:focus {
    // Pseudo-classes
  }
  
  &::before {
    // Pseudo-elements
  }
  
  .child-class {
    // Descendant selector
  }
  
  > .direct-child {
    // Direct child selector
  }
  
  @media (min-width: 768px) {
    // Responsive nesting
  }
}
```

## Core Nesting Principles

1. **Keep nesting shallow** (max 3-4 levels deep)
2. **Use `&` for modifiers and pseudo-selectors**
3. **Mirror HTML structure logically**
4. **Nest media queries inside selectors**
5. **Follow BEM for component architecture**

## Parent Selector (&) Usage

The `&` represents the parent selector and is essential for proper nesting:
```scss
.button {
  background: blue;
  
  // Modifier classes
  &--large { padding: 1rem 2rem; }
  &--small { padding: 0.25rem 0.5rem; }
  
  // Pseudo-classes
  &:hover { opacity: 0.9; }
  &:disabled { cursor: not-allowed; }
  
  // Pseudo-elements
  &::before { content: '→'; }
  
  // Context-based (parent comes after)
  .dark-mode & { background: darkgray; }
  
  // Compound selectors
  &.active { background: darkblue; }
  
  // Adjacent siblings
  & + & { margin-left: 0.5rem; }
}
```

## Child and Descendant Selectors
```scss
.menu {
  // All descendants
  .item {
    padding: 0.5rem;
  }
  
  // Direct children only
  > .item {
    border-bottom: 1px solid #eee;
  }
  
  // Adjacent sibling
  .item + .item {
    margin-top: 0.5rem;
  }
  
  // General sibling
  .header ~ .item {
    font-size: 0.9rem;
  }
}
```

## BEM Nesting Pattern

Block Element Modifier methodology pairs perfectly with SCSS nesting:
```scss
.card {
  // Block
  padding: 1rem;
  background: white;
  
  &__header {
    // Element
    font-weight: bold;
    margin-bottom: 1rem;
    
    &-icon {
      // Sub-element (use sparingly)
      margin-right: 0.5rem;
    }
  }
  
  &__body {
    // Element
    color: #333;
  }
  
  &__footer {
    // Element
    border-top: 1px solid #eee;
    padding-top: 1rem;
  }
  
  &--featured {
    // Modifier
    border: 2px solid gold;
    
    // Modify elements within this variant
    .card__header {
      color: gold;
    }
  }
  
  &--compact {
    // Modifier
    padding: 0.5rem;
    
    .card__body {
      font-size: 0.875rem;
    }
  }
}
```

## Responsive Nesting

Always nest media queries inside selectors for better organization:
```scss
.container {
  padding: 1rem;
  
  @media (min-width: 768px) {
    padding: 2rem;
  }
  
  @media (min-width: 1024px) {
    padding: 3rem;
    max-width: 1200px;
  }
  
  .content {
    font-size: 1rem;
    
    @media (min-width: 768px) {
      font-size: 1.125rem;
    }
  }
}
```

## Property Nesting

SCSS allows nesting properties that share a namespace:
```scss
.element {
  font: {
    family: 'Arial', sans-serif;
    size: 1rem;
    weight: 600;
  }
  
  border: {
    width: 1px;
    style: solid;
    color: gray;
    radius: 4px;
  }
  
  margin: {
    top: 1rem;
    bottom: 2rem;
  }
}
```

## Common Patterns

### Navigation Component
```scss
.nav {
  display: flex;
  gap: 1rem;
  
  &__item {
    position: relative;
    
    &--active {
      font-weight: bold;
      
      &::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        right: 0;
        height: 2px;
        background: currentColor;
      }
    }
    
    &:hover:not(&--active) {
      opacity: 0.7;
    }
  }
  
  &__link {
    color: inherit;
    text-decoration: none;
    
    &:focus-visible {
      outline: 2px solid blue;
      outline-offset: 2px;
    }
  }
}
```

### Form Component
```scss
.form {
  &__group {
    margin-bottom: 1rem;
    
    &:last-child {
      margin-bottom: 0;
    }
  }
  
  &__label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    
    &--required::after {
      content: '*';
      color: red;
      margin-left: 0.25rem;
    }
  }
  
  &__input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    
    &:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    &:invalid {
      border-color: #dc3545;
    }
    
    &[disabled] {
      background: #f5f5f5;
      cursor: not-allowed;
    }
  }
  
  &__error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }
}
```

### Grid Layout
```scss
.grid {
  display: grid;
  gap: 1rem;
  
  @media (min-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 1024px) {
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
  }
  
  &__item {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    
    &--featured {
      grid-column: 1 / -1;
      
      @media (min-width: 1024px) {
        grid-column: span 2;
      }
    }
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
    }
  }
}
```

## Anti-Patterns to Avoid
```scss
// ❌ AVOID: Excessive nesting depth
.page {
  .container {
    .row {
      .col {
        .card {
          .header {
            // Too deep! (6 levels)
          }
        }
      }
    }
  }
}

// ✅ PREFER: Flatten with BEM
.page { }
.page-container { }
.card { }
.card__header { }

// ❌ AVOID: Nesting unrelated selectors
.button {
  .icon {
    // Only nest if icon is specifically a button icon
  }
}

// ✅ PREFER: Keep independent
.button { }
.icon { }
.button .icon { } // Or create .button__icon

// ❌ AVOID: Over-qualifying selectors
.nav {
  ul.list {
    li.item {
      // Unnecessarily specific
    }
  }
}

// ✅ PREFER: Use classes
.nav {
  &__list { }
  &__item { }
}
```

## Best Practices

1. **Limit nesting to 3-4 levels maximum** - Keeps specificity manageable
2. **Use `&` for all variants and states** - Maintains parent context
3. **Nest media queries with selectors** - Improves maintainability
4. **Follow BEM naming** - Prevents naming conflicts
5. **Keep related styles together** - Easier to find and modify
6. **Use direct child selectors (>) when appropriate** - More performant and explicit
7. **Avoid nesting purely for organization** - Only nest what's truly related
8. **Test compiled output** - Ensure specificity is as expected
9. **Don't comment anything inside scss files** - Keep code clean of comments
10. **DON'T write font-family unless is requested** - Font is set in main scss file

## Reference Files

For more detailed guidance:

- **references/scss-patterns.md** - Comprehensive nesting patterns, BEM examples, complex structures
- **references/advanced-techniques.md** - Mixins, functions, dynamic generation, performance optimization

Load these files when you need specific patterns or advanced SCSS techniques.
