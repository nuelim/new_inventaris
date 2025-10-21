# Frontend Guideline Document

This document lays out the key decisions, structures, and principles for the frontend of the **new_inventaris** inventory management system. Even if you’re new to frontend work, this guide will help you understand how everything fits together and how to build on it. 

---

## 1. Frontend Architecture

1. **Framework and Language**
   - **React** with **TypeScript**: A popular combination that offers component-based development plus type safety.
   - **React Router** for page navigation.
   - **Redux Toolkit** for predictable, centralized state management.
   - **Axios** or **Fetch API** for talking to our backend REST API.

2. **Folder Structure (at a glance)**
   ```
   /src
     /assets      • images, icons, fonts
     /components  • reusable UI elements (buttons, inputs)
     /features    • feature-specific folders (inventory, reports)
     /pages       • top-level pages (Dashboard, Login)
     /services    • API calls and wrapper functions
     /store       • Redux slices and store setup
     /styles      • global CSS, variables, Tailwind config
     /utils       • helper functions, constants
     index.tsx    • app entry point
     App.tsx      • root component with routing setup
   ```

3. **Why this architecture?**
   - **Scalability**: Grouping by feature lets you add new modules (like a purchase-order plugin) without tangled imports.
   - **Maintainability**: Clear folders and naming conventions make it easy to find and update code.
   - **Performance**: Code splitting by route and lazy-loaded components keep initial downloads small.

---

## 2. Design Principles

1. **Usability**
   - Keep interfaces simple and consistent.
   - Provide clear feedback (loading spinners, success messages).

2. **Accessibility**
   - Use semantic HTML (buttons, headings, lists).
   - Follow WCAG guidelines: focus outlines, ARIA labels where needed.
   - Ensure color contrasts meet accessibility standards.

3. **Responsiveness**
   - Mobile-first approach: design for phones, then scale up.
   - Use flexible grids and breakpoints (e.g., 640px, 768px, 1024px).
   - Touch-friendly buttons and inputs.

4. **Consistency**
   - Adopt a shared component library so UI elements behave the same across the app.
   - Stick to a unified color palette, typography, and spacing scale.

---

## 3. Styling and Theming

1. **Styling Approach**
   - **Tailwind CSS** (utility-first) for rapid, consistent styling.
   - A small set of global CSS (in `/styles/globals.css`) for resets and key overrides.

2. **CSS Methodology**
   - Use Tailwind’s built-in classes rather than custom BEM. For very custom components, use CSS Modules to scope styles.

3. **Theming**
   - Define light/dark modes via Tailwind’s `dark:` variants.
   - Store theme preference in local storage and React Context.

4. **Visual Style**
   - **Design Style**: Modern flat UI with subtle shadows and rounded corners.
   - **Glassmorphism** for modal overlays and dashboards cards (transparent backgrounds with blur).

5. **Color Palette**
   ```
   Primary:   #4F46E5  (Indigo)
   Secondary: #F59E0B  (Amber)
   Success:   #10B981  (Emerald)
   Warning:   #FBBF24  (Yellow)
   Error:     #EF4444  (Red)
   Background:
     - Light:  #F3F4F6 (Gray-100)
     - Dark:   #1F2937 (Gray-800)
   Text:
     - Light:  #111827 (Gray-900)
     - Dark:   #E5E7EB (Gray-200)
   ```

6. **Typography**
   - **Font Family**: `Inter`, a clean, modern sans-serif.
   - **Headings**: Bold, larger sizes (e.g., h1: 2.25rem, h2: 1.875rem).
   - **Body Text**: 1rem with 1.5 line height.

---

## 4. Component Structure

1. **Component Types**
   - **Atoms**: Basic UI building blocks (Button, Input, Icon).
   - **Molecules**: Combinations of atoms (SearchBar, Card).
   - **Organisms**: Complex structures (InventoryTable, NotificationPanel).
   - **Templates/Pages**: Full screens built from organisms.

2. **Organization**
   - `/components/atoms` → smallest parts.
   - `/components/molecules` → small compositions.
   - `/features/inventory/components` → feature-specific components.
   - `/pages` → full-route components.

3. **Reusability**
   - Keep props minimal and predictable.
   - Document component props with JSDoc or TypeScript interfaces.
   - Export a single index file per folder for easier imports:
     ```js
     // components/atoms/index.ts
     export { default as Button } from './Button';
     export { default as Input } from './Input';
     ```

---

## 5. State Management

1. **Library**
   - **Redux Toolkit**: combines Redux, Immer, and Thunk out of the box.

2. **Slices**
   - Create one slice per domain (e.g., `inventorySlice`, `userSlice`, `alertsSlice`).
   - Organize async calls in `createAsyncThunk` functions.

3. **Local vs Global State**
   - Use component state (`useState`, `useReducer`) for UI-specific state (e.g., form values, open/close toggles).
   - Use Redux for shared data (inventory list, user profile, notifications).

4. **Provider Setup**
   ```tsx
   // index.tsx
   import { Provider } from 'react-redux';
   import store from './store';

   ReactDOM.render(
     <Provider store={store}>
       <App />
     </Provider>,
     document.getElementById('root')
   );
   ```

---

## 6. Routing and Navigation

1. **Library**
   - **React Router v6** for declarative routing.

2. **Route Configuration**
   - Centralize routes in `/src/routes/index.tsx`.
   - Define nested layouts (e.g., a main layout with sidebar, auth layout for login).

3. **Navigation Structure**
   - **Public Routes**: `/login`, `/signup`, `/password-reset`.
   - **Protected Routes**: `/dashboard`, `/inventory`, `/inventory/:id`, `/reports`, `/settings`.
   - Use a `RequireAuth` component to guard protected pages.

4. **Linking**
   - Use `<Link>` or `<NavLink>` for navigation; highlight active links.

---

## 7. Performance Optimization

1. **Code Splitting & Lazy Loading**
   - Lazy-load route components with `React.lazy` and `Suspense`.
     ```tsx
     const InventoryPage = React.lazy(() => import('../pages/InventoryPage'));
     ```

2. **Asset Optimization**
   - Compress images to WebP or optimized JPEG/PNG.
   - Inline small SVG icons as React components.

3. **Bundle Analysis**
   - Use tools like `source-map-explorer` or `webpack-bundle-analyzer`.
   - Keep vendor and application code separate.

4. **Caching and HTTP**
   - Leverage browser caching headers for static assets.
   - Use service workers (optional) for offline capabilities.

---

## 8. Testing and Quality Assurance

1. **Unit Tests**
   - **Jest** + **React Testing Library**: test individual components and hooks.
   - Aim for 70–80% coverage on critical slices and components.

2. **Integration Tests**
   - Combine multiple components, simulate flows (e.g., search for an item).

3. **End-to-End (E2E) Tests**
   - **Cypress**: automated browser tests for key user journeys (login, view inventory, create alert).

4. **Linting and Formatting**
   - **ESLint** with Airbnb or recommended React rules.
   - **Prettier** for consistent code style; run on commit with Husky + lint-staged.

5. **Continuous Integration**
   - Configure a pipeline (GitHub Actions, GitLab CI) to run tests and lint on every PR.

---

## 9. Conclusion and Overall Frontend Summary

This guide covers the core of our frontend setup for **new_inventaris**:

- A **React + TypeScript** foundation that scales with your needs.
- Clear **design principles**—usability, accessibility, responsiveness, and consistency.
- A **component-based** approach with Tailwind CSS-driven styling and theming.
- **Redux Toolkit** for shared state, **React Router** for navigation, and **lazy loading** for performance.
- A solid **testing** and **CI** strategy to keep the codebase reliable.

By following these guidelines, every developer—regardless of background—can confidently work on the frontend, add new features, and maintain a consistent, high-quality user experience across the application.