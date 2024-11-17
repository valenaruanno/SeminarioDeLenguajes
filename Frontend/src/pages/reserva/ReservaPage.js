import React, { useState, useEffect } from 'react';
import FooterComponent from '../../components/FooterComponent';
import HeaderComponent from '../../components/HeaderComponent';
import NavbarComponent from '../../components/NavbarComponent';
import ReservaComponent from '../../components/ReservaComponent';
const ReservaPage = ()=> {
    return (
        <div>
            <HeaderComponent/>
            <NavbarComponent/>
            <ReservaComponent/>
            <FooterComponent/>
        </div>
    )
}

export default ReservaPage