export function getCharacterSheetPath(character) {
  if (character?.sheetThreadSlug) {
    return `/threads/${character.sheetThreadSlug}`;
  }
  return null;
}
