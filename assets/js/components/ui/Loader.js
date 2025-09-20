import React from 'react';

const Loader = ({ message = 'Ładuję dane...' }) => (
  <div className='loader-container'>
    <div className='spinner'></div>
    <p>{message}</p>
  </div>
);

export default Loader;
