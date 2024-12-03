### Added
- new public methods for external manipulation:
  - `$helper->GetItems(): array<Item> $items;`,
  - `$helper->SetItems(array<Item> $items): static;`,
  - `$helper->UnsetItems(int $index): bool;`.

### Changed
- internal properties names refactoring.