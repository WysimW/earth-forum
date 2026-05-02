import React from 'react';
import { Card, Input, Select, Space, Button } from 'antd';
import { SearchOutlined, ReloadOutlined } from '@ant-design/icons';

const { Search } = Input;
const { Option } = Select;

const Filters = ({ 
  searchPlaceholder = 'Rechercher...',
  searchValue,
  onSearchChange,
  onSearch,
  filters = [],
  onReset,
}) => {
  const hasFilters = filters.length > 0 || searchValue;

  return (
    <Card style={{ marginBottom: 16 }}>
      <Space direction="vertical" style={{ width: '100%' }} size="middle">
        <Space wrap style={{ width: '100%' }}>
          <Search
            placeholder={searchPlaceholder}
            allowClear
            value={searchValue}
            onChange={(e) => onSearchChange(e.target.value)}
            onSearch={onSearch}
            style={{ width: 300 }}
            enterButton={<SearchOutlined />}
          />
          {filters.map((filter, index) => (
            <Select
              key={index}
              placeholder={filter.placeholder}
              value={filter.value}
              onChange={filter.onChange}
              style={{ width: filter.width || 200 }}
              allowClear={filter.allowClear !== false}
            >
              {filter.options?.map((option) => (
                <Option key={option.value} value={option.value}>
                  {option.label}
                </Option>
              ))}
            </Select>
          ))}
          {hasFilters && onReset && (
            <Button icon={<ReloadOutlined />} onClick={onReset}>
              Réinitialiser
            </Button>
          )}
        </Space>
      </Space>
    </Card>
  );
};

export default Filters;






