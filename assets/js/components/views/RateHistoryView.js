import React from 'react';
import { Link } from 'react-router-dom';
import { Table, Loader } from '../ui';

const RateHistoryView = ({
  loading,
  items,
  code,
  date,
  error,
  onDateChange,
  defaultDate,
  hasBack,
}) => {
  const columns = ['Data', 'Średni', 'Kupno', 'Sprzedaż'];

  const formatDateWithDay = (dateString) => {
    const date = new Date(dateString);
    const dayNames = ['ND', 'PON', 'WT', 'ŚR', 'CZW', 'PT', 'SOB'];
    const dayName = dayNames[date.getDay()];
    return `${dateString} (${dayName})`;
  };

  const renderRow = (item, idx) => (
    <tr key={idx}>
      <td>{formatDateWithDay(item.date)}</td>
      <td>{item.mid.toFixed(4)}</td>
      <td>{item.buy !== null ? item.buy.toFixed(4) : '-'}</td>
      <td>{item.sell.toFixed(4)}</td>
    </tr>
  );

  return (
    <div className='container mt-4'>
      <div className='d-flex align-items-center justify-content-between'>
        <div className='d-flex flex-column'>
          {hasBack && (
            <Link
              to='/rates'
              className='btn btn-secondary btn-sm align-self-start'
            >
              Powrót
            </Link>
          )}
          <h3 className='mb-0 mt-2'>Historia kursu: {code}</h3>
        </div>
        <div>
          <label className='mr-2'>Pokaż 14 dni przed datą:</label>
          <input
            type='date'
            value={date}
            onChange={onDateChange}
            disabled={loading}
            max={defaultDate}
          />
        </div>
      </div>
      <div className='mt-3'>
        {loading ? (
          <Loader message='Ładuję historię kursu…' />
        ) : error ? (
          <div className='alert alert-warning' role='alert'>
            <h6 className='alert-heading'>
              <i className='me-2'></i>
              Uwaga
            </h6>
            <p className='mb-0'>{error}</p>
          </div>
        ) : items.length > 0 ? (
          <Table columns={columns} data={items} renderRow={renderRow} />
        ) : (
          <div className='alert alert-info'>
            <p className='mb-0'>Brak danych historycznych dla wybranej daty.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default RateHistoryView;
