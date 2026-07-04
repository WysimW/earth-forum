import React, { useCallback, useEffect, useReducer, useRef, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { TextStyle } from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import { FaBold, FaEraser, FaFaceSmile, FaImage, FaItalic, FaLink, FaPalette } from 'react-icons/fa6';
import api from '../../services/api';
import {
  isSubstantialMessagingHtml,
  messagingHtmlDomEquivalent,
  sanitizeMessagingHtml,
} from '../../utils/messagingContent';
import styles from './MessagingComposer.module.css';

const EMOJI_PRESET = [
  '😀',
  '😂',
  '😊',
  '😉',
  '😍',
  '🤔',
  '😢',
  '👍',
  '👎',
  '❤️',
  '🔥',
  '✨',
  '🎉',
  '👀',
  '💬',
  '🙏',
  '🤣',
  '😅',
  '👋',
  '⭐',
  '✅',
  '❌',
  '📎',
  '🔗',
];

const MessagingComposer = ({ value, onChange, disabled = false, placeholder = 'Votre message…' }) => {
  const fileInputRef = useRef(null);
  const [emojiOpen, setEmojiOpen] = useState(false);
  const [imageUploading, setImageUploading] = useState(false);
  const [imageError, setImageError] = useState('');
  const [showPlaceholder, setShowPlaceholder] = useState(() => !isSubstantialMessagingHtml(value || ''));
  const [, toolbarTick] = useReducer((n) => n + 1, 0);

  const emitChange = useCallback(
    (editorInstance) => {
      const html = sanitizeMessagingHtml(editorInstance.getHTML());
      onChange(html);
      setShowPlaceholder(!isSubstantialMessagingHtml(html));
    },
    [onChange]
  );

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: false,
        bulletList: false,
        orderedList: false,
        blockquote: false,
        codeBlock: false,
        horizontalRule: false,
        strike: false,
      }),
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          rel: 'noopener noreferrer',
          target: '_blank',
        },
      }),
      Image,
      TextStyle,
      Color.configure({ types: ['textStyle'] }),
    ],
    content: value || '',
    editable: !disabled,
    editorProps: {
      attributes: {
        class: 'tiptap',
        spellcheck: 'true',
      },
    },
    onUpdate: ({ editor: ed }) => {
      emitChange(ed);
    },
  });

  useEffect(() => {
    if (!editor) return;
    editor.setEditable(!disabled);
  }, [editor, disabled]);

  useEffect(() => {
    if (!editor) return undefined;
    const bump = () => toolbarTick();
    editor.on('selectionUpdate', bump);
    editor.on('transaction', bump);
    return () => {
      editor.off('selectionUpdate', bump);
      editor.off('transaction', bump);
    };
  }, [editor]);

  useEffect(() => {
    if (!editor) return;
    const incoming = value || '';
    const fromEditor = editor.getHTML();
    if (messagingHtmlDomEquivalent(incoming, fromEditor)) {
      setShowPlaceholder(!isSubstantialMessagingHtml(sanitizeMessagingHtml(incoming)));
      return;
    }
    const safe = sanitizeMessagingHtml(incoming);
    editor.commands.setContent(safe || '<p></p>', false);
    setShowPlaceholder(!isSubstantialMessagingHtml(safe));
  }, [editor, value]);

  useEffect(() => {
    if (!emojiOpen) return undefined;
    const close = (e) => {
      if (e.target.closest?.(`[data-messaging-emoji-popover="1"]`)) return;
      setEmojiOpen(false);
    };
    document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, [emojiOpen]);

  const setLink = () => {
    if (!editor || disabled) return;
    const previous = editor.getAttributes('link').href;
    const url = window.prompt("Adresse du lien (URL) :", previous || 'https://');
    if (url === null) return;
    const trimmed = url.trim();
    if (trimmed === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      return;
    }
    editor.chain().focus().extendMarkRange('link').setLink({ href: trimmed }).run();
  };

  const uploadImage = async (file) => {
    if (!editor || disabled) return;
    if (!file || !file.type.startsWith('image/')) {
      setImageError('Choisis une image valide.');
      return;
    }
    setImageUploading(true);
    setImageError('');
    const formData = new FormData();
    formData.append('file', file);
    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      const uploadedUrl = response?.data?.url || '';
      if (!uploadedUrl) throw new Error('URL manquante');
      editor.chain().focus().setImage({ src: uploadedUrl, alt: '' }).run();
    } catch (err) {
      setImageError(err?.response?.data?.error || "Envoi de l'image impossible.");
    } finally {
      setImageUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const insertEmoji = (ch) => {
    if (!editor || disabled) return;
    editor.chain().focus().insertContent(ch).run();
    setEmojiOpen(false);
  };

  const boldActive = editor?.isActive('bold');
  const italicActive = editor?.isActive('italic');
  const linkActive = editor?.isActive('link');

  return (
    <div className={styles.wrap}>
      <div className={styles.toolbar} aria-label="Mise en forme du message">
        <button
          type="button"
          className={`${styles.toolbarBtn} ${boldActive ? styles.toolbarBtnActive : ''}`}
          title="Gras"
          disabled={disabled || !editor}
          onClick={() => editor?.chain().focus().toggleBold().run()}
        >
          <FaBold className={styles.toolbarIcon} aria-hidden />
        </button>
        <button
          type="button"
          className={`${styles.toolbarBtn} ${italicActive ? styles.toolbarBtnActive : ''}`}
          title="Italique"
          disabled={disabled || !editor}
          onClick={() => editor?.chain().focus().toggleItalic().run()}
        >
          <FaItalic className={styles.toolbarIcon} aria-hidden />
        </button>
        <label className={styles.colorLabel} title="Couleur du texte">
          <input
            type="color"
            className={styles.colorInput}
            disabled={disabled || !editor}
            aria-label="Couleur du texte"
            defaultValue="#c8d0dc"
            onInput={(e) => {
              const v = e.currentTarget.value;
              editor?.chain().focus().setColor(v).run();
            }}
          />
          <FaPalette className={styles.toolbarIcon} aria-hidden />
        </label>
        <button
          type="button"
          className={styles.toolbarBtn}
          title="Couleur par défaut"
          disabled={disabled || !editor}
          onClick={() => editor?.chain().focus().unsetColor().run()}
        >
          <FaEraser className={styles.toolbarIcon} aria-hidden />
        </button>
        <button
          type="button"
          className={`${styles.toolbarBtn} ${linkActive ? styles.toolbarBtnActive : ''}`}
          title="Lien"
          disabled={disabled || !editor}
          onClick={setLink}
        >
          <FaLink className={styles.toolbarIcon} aria-hidden />
        </button>
        <button
          type="button"
          className={styles.toolbarBtn}
          title="Insérer une image (fichier)"
          disabled={disabled || !editor || imageUploading}
          onClick={() => fileInputRef.current?.click()}
        >
          <FaImage className={styles.toolbarIcon} aria-hidden />
        </button>
        <div className={styles.emojiPopover} data-messaging-emoji-popover="1">
          <button
            type="button"
            className={`${styles.toolbarBtn} ${emojiOpen ? styles.toolbarBtnActive : ''}`}
            title="Emoji"
            disabled={disabled || !editor}
            data-messaging-emoji-popover="1"
            onClick={() => setEmojiOpen((o) => !o)}
          >
            <FaFaceSmile className={styles.toolbarIcon} aria-hidden />
          </button>
          {emojiOpen && (
            <div className={styles.emojiPanel} data-messaging-emoji-popover="1" role="listbox" aria-label="Emojis">
              {EMOJI_PRESET.map((em) => (
                <button
                  key={em}
                  type="button"
                  className={styles.emojiCell}
                  data-messaging-emoji-popover="1"
                  onClick={() => insertEmoji(em)}
                >
                  {em}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        className={styles.hiddenFile}
        tabIndex={-1}
        aria-hidden
        onChange={(ev) => uploadImage(ev.target.files?.[0])}
      />

      {imageError ? (
        <p className={styles.imageError} role="alert">
          {imageError}
        </p>
      ) : null}

      <div className={styles.editorShell}>
        {showPlaceholder ? (
          <span className={styles.placeholderOverlay} aria-hidden>
            {placeholder}
          </span>
        ) : null}
        <EditorContent editor={editor} className={styles.editorRoot} />
      </div>

      <p className={styles.hint}>Images : envoi par fichier uniquement. Les liens s’ouvrent dans un nouvel onglet.</p>
    </div>
  );
};

export default MessagingComposer;
