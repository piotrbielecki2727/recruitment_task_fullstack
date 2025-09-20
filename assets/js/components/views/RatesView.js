import React from 'react';
import { Link } from 'react-router-dom';
import { Table, Loader } from '../ui';

const RatesView = ({ loading, items, date, error }) => {
  const columns = ['Waluta', 'Średni', 'Kupno', 'Sprzedaż', ''];

  const renderRow = (item) => (
    <tr key={item.code}>
      <td>{item.code}</td>
      <td>{item.mid.toFixed(4)}</td>
      <td>{item.buy !== null ? item.buy.toFixed(4) : '-'}</td>
      <td>{item.sell.toFixed(4)}</td>
      <td>
        <Link
          className={'btn btn-sm btn-secondary'}
          to={`/rates/${item.code}`}
          style={{ width: 'auto', padding: '0.25rem 0.5rem' }}
        >
          Historia
        </Link>
      </td>
    </tr>
  );

  return (
    <div className='container mt-4'>
      <h3>Bieżące kursy</h3>
      <div style={{ height: '24px', marginBottom: '8px' }}>
        {loading ? (
          <p className='text-muted mb-0'>Data: ładowanie...</p>
        ) : date ? (
          <p className='text-muted mb-0'>Data: {date}</p>
        ) : null}
      </div>
      {loading ? (
        <Loader message='Ładuję aktualne kursy walut…' />
      ) : error ? (
        <div className={'alert alert-danger'}>{error}</div>
      ) : (
        <Table columns={columns} data={items} renderRow={renderRow} />
      )}
    </div>
  );
};

export default RatesView;
