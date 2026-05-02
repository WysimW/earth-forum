import React from 'react';
import ListPageBreadcrumbs from './Breadcrumbs';
import ActionsCard from './ActionsCard';
import Filters from './Filters';

const ListPageLayout = ({
  breadcrumbs = [],
  actions = [],
  searchPlaceholder,
  searchValue,
  onSearchChange,
  onSearch,
  filters = [],
  onResetFilters,
  extraContent,
  children,
}) => {
  return (
    <div>
      <ListPageBreadcrumbs items={breadcrumbs} />
      <ActionsCard actions={actions} extraContent={extraContent} />
      <Filters
        searchPlaceholder={searchPlaceholder}
        searchValue={searchValue}
        onSearchChange={onSearchChange}
        onSearch={onSearch}
        filters={filters}
        onReset={onResetFilters}
      />
      {children}
    </div>
  );
};

export default ListPageLayout;

