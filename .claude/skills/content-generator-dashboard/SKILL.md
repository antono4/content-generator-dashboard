```markdown
# content-generator-dashboard Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill teaches you the core development conventions and workflows used in the `content-generator-dashboard` TypeScript project. You'll learn about file naming, import/export styles, commit message conventions, and how to write and run tests according to the project's patterns. This guide is ideal for new contributors or anyone looking to align their code with the repository's established standards.

## Coding Conventions

### File Naming
- **Pattern:** PascalCase
- **Example:**  
  - `ContentGenerator.ts`
  - `DashboardView.tsx`

### Import Style
- **Pattern:** Relative imports
- **Example:**
  ```typescript
  import { DashboardView } from './DashboardView';
  ```

### Export Style
- **Pattern:** Named exports
- **Example:**
  ```typescript
  // In ContentGenerator.ts
  export function generateContent() { ... }
  
  // Usage
  import { generateContent } from './ContentGenerator';
  ```

### Commit Messages
- **Pattern:** Conventional commits with type prefixes
- **Common Prefix:** `docs`
- **Example:**  
  ```
  docs: update README with usage instructions
  ```

## Workflows

### Documenting Code Changes
**Trigger:** When updating or adding documentation.
**Command:** `/docs-update`

1. Make your documentation changes.
2. Use a conventional commit message with the `docs` prefix.
   - Example: `docs: add API usage section`
3. Push your changes to the repository.

### Adding New Features or Modules
**Trigger:** When creating new TypeScript files or modules.
**Command:** `/add-module`

1. Name new files using PascalCase (e.g., `NewFeature.ts`).
2. Use relative imports for dependencies.
3. Export functions or components using named exports.
4. Write or update tests in a corresponding `*.test.*` file.

### Writing and Running Tests
**Trigger:** When adding or updating features.
**Command:** `/run-tests`

1. Create or update test files matching the `*.test.*` pattern (e.g., `ContentGenerator.test.ts`).
2. Write tests according to the project's testing framework (unknown, but follow TypeScript best practices).
3. Run the test suite using the project's test runner (refer to project documentation or package scripts).

## Testing Patterns

- **Test File Pattern:** Files should be named with the `.test.` infix, such as `Component.test.ts`.
- **Framework:** Not explicitly detected; use TypeScript-compatible testing frameworks (e.g., Jest, Mocha).
- **Example:**
  ```typescript
  // ContentGenerator.test.ts
  import { generateContent } from './ContentGenerator';

  describe('generateContent', () => {
    it('should generate content as expected', () => {
      const result = generateContent();
      expect(result).toBeDefined();
    });
  });
  ```

## Commands
| Command        | Purpose                                      |
|----------------|----------------------------------------------|
| /docs-update   | Document code changes or update documentation|
| /add-module    | Add a new feature or module                  |
| /run-tests     | Run the test suite                           |
```