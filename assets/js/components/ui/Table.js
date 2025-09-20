import React from 'react';

const Table = ({
  columns,
  data,
  renderRow,
  className = 'table table-striped',
}) => (
  <div className='table-responsive fade-in'>
    <table className={className}>
      <thead>
        <tr>
          {columns.map((column, idx) => (
            <th key={idx}>{column}</th>
          ))}
        </tr>
      </thead>
      <tbody>{data.map((item, idx) => renderRow(item, idx))}</tbody>
    </table>
  </div>
);

export default Table;
