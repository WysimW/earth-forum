import React, { useEffect, useMemo, useRef, useState } from 'react';
import styles from './RichTextComposer.module.css';
import {
  applyDialogueAutoDetect,
  applyDialogueThemeToSelection,
  countDialogueCandidates,
  removeDialogueThemeFromSelection,
} from '../../utils/dialogueTheme';

const INLINE_COMMANDS = [
  { id: 'bold', label: 'B', title: 'Gras' },
  { id: 'italic', label: 'I', title: 'Italique' },
  { id: 'underline', label: 'U', title: 'Souligné' },
  { id: 'strikeThrough', label: 'S', title: 'Barré' },
];

const BLOCK_COMMANDS = [
  { id: 'insertUnorderedList', label: '•', title: 'Liste à puces' },
  { id: 'insertOrderedList', label: '1.', title: 'Liste numérotée' },
  { id: 'outdent', label: '<', title: 'Diminuer le retrait' },
  { id: 'indent', label: '>', title: 'Augmenter le retrait' },
  { id: 'justifyLeft', label: '≡L', title: 'Aligner à gauche' },
  { id: 'justifyCenter', label: '≡C', title: 'Centrer' },
  { id: 'justifyRight', label: '≡R', title: 'Aligner à droite' },
];

const UTILITY_COMMANDS = [
  { id: 'undo', label: '↶', title: 'Annuler' },
  { id: 'redo', label: '↷', title: 'Rétablir' },
  { id: 'removeFormat', label: 'Tx', title: 'Nettoyer le format' },
];

const stripBackgroundStyles = (html) => {
  if (!html) return '';

  const container = document.createElement('div');
  container.innerHTML = html;

  const elements = container.querySelectorAll('*');
  elements.forEach((element) => {
    if (element instanceof HTMLElement) {
      element.style.removeProperty('background');
      element.style.removeProperty('background-color');
      if (!element.getAttribute('style')?.trim()) {
        element.removeAttribute('style');
      }
    }
    element.removeAttribute('bgcolor');
  });

  return container.innerHTML;
};

const RichTextComposer = ({
  value,
  onChange,
  onQuote,
  dialogueThemes = [],
  activeDialogueThemeId = '',
  onActiveDialogueThemeIdChange = null,
}) => {
  const editorRef = useRef(null);
  const savedSelectionRef = useRef(null);
  const [textColor, setTextColor] = useState('#ffffff');

  useEffect(() => {
    if (!editorRef.current) return;
    if (editorRef.current.innerHTML !== value) {
      editorRef.current.innerHTML = value || '';
    }
  }, [value]);

  const plainLength = useMemo(() => {
    const temp = document.createElement('div');
    temp.innerHTML = value || '';
    return temp.textContent?.length ?? 0;
  }, [value]);

  const liveDialogueCandidates = useMemo(() => countDialogueCandidates(value || ''), [value]);

  const selectedDialogueTheme = useMemo(() => {
    if (!activeDialogueThemeId) return null;
    return dialogueThemes.find((theme) => String(theme.id) === String(activeDialogueThemeId)) || null;
  }, [dialogueThemes, activeDialogueThemeId]);

  const focusEditor = () => {
    editorRef.current?.focus();
  };

  const saveEditorSelection = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editorRef.current) return;
    const range = selection.getRangeAt(0);
    if (editorRef.current.contains(range.commonAncestorContainer)) {
      savedSelectionRef.current = range.cloneRange();
    }
  };

  const restoreEditorSelection = () => {
    if (!savedSelectionRef.current || !editorRef.current) return;
    const selection = window.getSelection();
    if (!selection) return;
    selection.removeAllRanges();
    selection.addRange(savedSelectionRef.current);
  };

  const execute = (command, commandValue) => {
    focusEditor();
    restoreEditorSelection();
    document.execCommand(command, false, commandValue);
    onChange(stripBackgroundStyles(editorRef.current?.innerHTML || ''));
  };

  const applyBlockFormat = (event) => {
    const format = event.target.value;
    if (!format) return;
    execute('formatBlock', format);
  };

  const insertLink = () => {
    focusEditor();
    restoreEditorSelection();
    const url = window.prompt('URL du lien');
    if (!url) return;
    execute('createLink', url);
  };

  const insertImage = () => {
    focusEditor();
    restoreEditorSelection();
    const url = window.prompt('URL de l image');
    if (!url) return;
    execute('insertImage', url);
  };

  const insertHr = () => {
    execute('insertHorizontalRule');
  };

  const applyDialogueTheme = () => {
    if (!editorRef.current || !selectedDialogueTheme) return;
    focusEditor();
    restoreEditorSelection();
    const changed = applyDialogueThemeToSelection(editorRef.current, selectedDialogueTheme);
    if (changed) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const clearDialogueTheme = () => {
    if (!editorRef.current) return;
    focusEditor();
    restoreEditorSelection();
    const changed = removeDialogueThemeFromSelection(editorRef.current);
    if (changed) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const runDialogueAutoDetect = () => {
    if (!editorRef.current || !selectedDialogueTheme) return;
    focusEditor();
    restoreEditorSelection();
    const count = applyDialogueAutoDetect(editorRef.current, selectedDialogueTheme);
    if (count > 0) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const preventFocusLoss = (event) => {
    event.preventDefault();
  };

  const applyTextColor = (nextColor) => {
    if (!nextColor) return;
    setTextColor(nextColor);
    focusEditor();
    restoreEditorSelection();
    document.execCommand('styleWithCSS', false, true);
    document.execCommand('foreColor', false, nextColor);
    onChange(stripBackgroundStyles(editorRef.current?.innerHTML || ''));
  };

  const handleInput = (event) => {
    const cleanedHtml = stripBackgroundStyles(event.currentTarget.innerHTML);
    if (cleanedHtml !== event.currentTarget.innerHTML) {
      event.currentTarget.innerHTML = cleanedHtml;
    }
    onChange(cleanedHtml);
  };

  const handlePaste = (event) => {
    event.preventDefault();
    const clipboard = event.clipboardData || window.clipboardData;
    const html = clipboard?.getData('text/html');
    const text = clipboard?.getData('text/plain');

    focusEditor();
    if (html) {
      document.execCommand('insertHTML', false, stripBackgroundStyles(html));
    } else if (text) {
      document.execCommand('insertText', false, text);
    }

    onChange(stripBackgroundStyles(editorRef.current?.innerHTML || ''));
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.toolbar}>
        <select className={styles.toolbarSelect} onChange={applyBlockFormat} defaultValue="">
          <option value="">Style</option>
          <option value="p">Paragraphe</option>
          <option value="h2">Titre 2</option>
          <option value="h3">Titre 3</option>
          <option value="h4">Titre 4</option>
          <option value="blockquote">Citation</option>
          <option value="pre">Code bloc</option>
        </select>

        {INLINE_COMMANDS.map((command) => (
          <button
            key={command.id}
            type="button"
            className={styles.toolbarButton}
            onMouseDown={preventFocusLoss}
            onClick={() => execute(command.id, command.value)}
            title={command.title}
            aria-label={command.title}
          >
            {command.label}
          </button>
        ))}

        {BLOCK_COMMANDS.map((command) => (
          <button
            key={command.id}
            type="button"
            className={styles.toolbarButton}
            onMouseDown={preventFocusLoss}
            onClick={() => execute(command.id)}
            title={command.title}
            aria-label={command.title}
          >
            {command.label}
          </button>
        ))}

        <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertLink} title="Insérer un lien" aria-label="Insérer un lien">
          🔗
        </button>
        <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertImage} title="Insérer une image" aria-label="Insérer une image">
          🖼
        </button>
        <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertHr} title="Insérer un séparateur" aria-label="Insérer un séparateur">
          ―
        </button>

        <label className={styles.colorPickerLabel} title="Couleur du texte">
          A
          <input
            type="color"
            className={styles.colorPickerInput}
            value={textColor}
            onChange={(event) => applyTextColor(event.target.value)}
          />
        </label>

        {UTILITY_COMMANDS.map((command) => (
          <button
            key={command.id}
            type="button"
            className={styles.toolbarButton}
            onMouseDown={preventFocusLoss}
            onClick={() => execute(command.id)}
            title={command.title}
            aria-label={command.title}
          >
            {command.label}
          </button>
        ))}

        {onQuote && (
          <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={onQuote} title="Insérer une citation RP" aria-label="Insérer une citation RP">
            "
          </button>
        )}

        {dialogueThemes.length > 0 && (
          <>
            <select
              className={styles.toolbarSelect}
              value={activeDialogueThemeId || ''}
              onChange={(event) => onActiveDialogueThemeIdChange?.(event.target.value)}
              title="Thème de dialogue actif"
            >
              <option value="">Theme dialogue</option>
              {dialogueThemes.map((theme) => (
                <option key={theme.id} value={theme.id}>
                  {theme.name}{theme.isDefault ? ' (defaut)' : ''}
                </option>
              ))}
            </select>
            <button
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={applyDialogueTheme}
              title="Appliquer ou retirer le thème sur la sélection"
              aria-label="Appliquer ou retirer le thème sur la sélection"
              disabled={!selectedDialogueTheme}
            >
              💬
            </button>
            <button
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={clearDialogueTheme}
              title="Retirer le style dialogue de la sélection"
              aria-label="Retirer le style dialogue de la sélection"
            >
              ∅
            </button>
            <button
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={runDialogueAutoDetect}
              title="Auto-détecter les dialogues entre guillemets"
              aria-label="Auto-détecter les dialogues entre guillemets"
              disabled={!selectedDialogueTheme}
            >
              “”
            </button>
          </>
        )}
      </div>

      <div
        ref={editorRef}
        className={styles.editor}
        contentEditable
        suppressContentEditableWarning
        onInput={handleInput}
        onPaste={handlePaste}
        onMouseUp={saveEditorSelection}
        onKeyUp={saveEditorSelection}
        onFocus={saveEditorSelection}
      />
      {dialogueThemes.length > 0 && (
        <div className={styles.dialogueMeta}>
          Suggestions dialogue detectees: {liveDialogueCandidates}
        </div>
      )}
      <div className={styles.meta}>Longueur: {plainLength} caracteres</div>
    </div>
  );
};

export default RichTextComposer;
