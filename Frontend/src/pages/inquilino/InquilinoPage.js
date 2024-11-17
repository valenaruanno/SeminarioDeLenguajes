import React, { useState, useEffect} from 'react';
import FooterComponent from '../../components/FooterComponent';
import HeaderComponent from '../../components/HeaderComponent';
import NavbarComponent from '../../components/NavbarComponent';
import InquilinoComponent from '../../components/InquilinoComponent';
const InquilinoPage = ()=> {
    return  (
        <div>
            <HeaderComponent/>
            <NavbarComponent/>
            <InquilinoComponent/>
            <FooterComponent/>
        </div>
    )
}

export default InquilinoPage;