using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace service
{
    /// <summary>
    /// Логика взаимодействия для УслугиAddAdminWindow.xaml
    /// </summary>
    public partial class УслугиAddAdminWindow : Window
    {
        public serviceEntities3 _context;

        public bool IsEdit;
        public УслугиAddAdminWindow()
        {
            InitializeComponent();
            IsEdit = false;
            _context = serviceEntities3.GetContext();
            DataContext = new Услуги();
        }

        public УслугиAddAdminWindow(Услуги услуги)
        {
            InitializeComponent();
            IsEdit = true;
            _context = serviceEntities3.GetContext();
            DataContext = услуги;
        }


        private void AddOrChange(object sender, RoutedEventArgs e)
        {
            if (IsEdit)
            {
                _context.SaveChanges();
            }
            else 
            {
                _context.Услуги.Add(DataContext as Услуги);
                _context.SaveChanges();
            }
            this.Close();
        }
    }
}
