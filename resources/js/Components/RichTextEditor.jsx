import { useEffect, useRef } from 'react';

function exec(cmd, value = null) {
    try {
        document.execCommand(cmd, false, value);
    } catch (e) {
        // noop
    }
}

const TOOLS = [
    { label: 'B', title: 'Bold', cls: 'font-bold', run: () => exec('bold') },
    { label: 'I', title: 'Italic', cls: 'italic', run: () => exec('italic') },
    { label: 'U', title: 'Underline', cls: 'underline', run: () => exec('underline') },
    { label: 'S', title: 'Strikethrough', cls: 'line-through', run: () => exec('strikeThrough') },
    { label: 'H2', title: 'Heading 2', cls: '', run: () => exec('formatBlock', '<h2>') },
    { label: 'H3', title: 'Heading 3', cls: '', run: () => exec('formatBlock', '<h3>') },
    { label: '¶', title: 'Paragraph', cls: '', run: () => exec('formatBlock', '<p>') },
    { label: '• List', title: 'Bullet list', cls: '', run: () => exec('insertUnorderedList') },
    { label: '1. List', title: 'Numbered list', cls: '', run: () => exec('insertOrderedList') },
    { label: '❝', title: 'Blockquote', cls: '', run: () => exec('formatBlock', '<blockquote>') },
    { label: '</>', title: 'Code block', cls: '', run: () => exec('formatBlock', '<pre>') },
    {
        label: '🔗', title: 'Link', cls: '', run: () => {
            const url = window.prompt('Link URL:');
            if (url) exec('createLink', url);
        },
    },
    { label: '✕', title: 'Clear formatting', cls: '', run: () => exec('removeFormat') },
];

const styles = `
.nomdal-editor-content:focus { outline: none; }
.nomdal-editor-content:empty::before { content: attr(data-placeholder); color: #9ca3af; }
.nomdal-editor-content h2 { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.nomdal-editor-content h3 { font-size: 1.2rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.nomdal-editor-content p { margin: 0 0 0.75rem; }
.nomdal-editor-content ul { list-style: disc; padding-left: 1.5rem; margin: 0 0 0.75rem; }
.nomdal-editor-content ol { list-style: decimal; padding-left: 1.5rem; margin: 0 0 0.75rem; }
.nomdal-editor-content a { color: #2563eb; text-decoration: underline; }
.nomdal-editor-content blockquote { border-left: 3px solid #d1d5db; padding-left: 0.75rem; margin: 0.75rem 0; color: #6b7280; }
.nomdal-editor-content pre { background: #1f2937; color: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-family: monospace; font-size: 0.85rem; margin: 0.75rem 0; }
.nomdal-editor-content img { max-width: 100%; border-radius: 0.5rem; }
`;

export default function RichTextEditor({ value = '', onChange, placeholder = 'Write your post…' }) {
    const ref = useRef(null);

    useEffect(() => {
        if (ref.current && ref.current.innerHTML !== value) {
            ref.current.innerHTML = value || '';
        }
    }, []); // set once on mount; the parent remounts per post

    const handleInput = () => onChange?.(ref.current.innerHTML);

    return (
        <div className="rounded-xl border border-gray-300 bg-white overflow-hidden">
            <style>{styles}</style>
            <div className="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-2">
                {TOOLS.map((t) => (
                    <button
                        key={t.title}
                        type="button"
                        title={t.title}
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => { ref.current?.focus(); t.run(); }}
                        className={`min-w-[28px] px-2 py-1 text-sm rounded-md text-gray-700 hover:bg-gray-200 ${t.cls}`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>
            <div
                ref={ref}
                contentEditable
                suppressContentEditableWarning
                className="nomdal-editor-content min-h-[340px] px-4 py-3 text-gray-900 text-base"
                data-placeholder={placeholder}
                onInput={handleInput}
            />
        </div>
    );
}
