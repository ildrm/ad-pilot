declare namespace JSX {
  type Element = any;
  interface ElementChildrenAttribute {
    children: {};
  }
  interface IntrinsicAttributes {
    key?: string | number;
  }
  interface IntrinsicElements {
    [name: string]: any;
  }
}
declare const wp: {
  element: {
    createElement: (...args: any[]) => any;
    Fragment: any;
    createRoot?: (node: HTMLElement) => { render: (element: any) => void };
    render?: (element: any, node: HTMLElement) => void;
    useState: <T>(
      value: T | (() => T),
    ) => [T, (value: T | ((previous: T) => T)) => void];
    useEffect: (effect: () => void | (() => void), deps?: unknown[]) => void;
    useRef: <T>(value: T) => { current: T };
    useId?: () => string;
  };
  i18n?: { __: (text: string, domain?: string) => string };
  blocks: { registerBlockType: (name: string, config: any) => void };
  components: Record<string, any>;
  blockEditor: { InspectorControls: any; useBlockProps: (props?: any) => any };
};
interface Window {
  adpilotEditor?: { restUrl: string; nonce: string; canViewInventory: boolean };
  adpilotConfig?: {
    restUrl: string;
    nonce: string;
    mode: "admin" | "portal";
    capabilities: string[];
    userId?: number;
    siteId?: number;
  };
}
