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
    /// Логика взаимодействия для OrdersAdminWindow.xaml
    /// </summary>
    public partial class OrdersAdminWindow : Window
    {
        public serviceEntities3 _context;
        public OrdersAdminWindow()
        {
            InitializeComponent();
            _context = serviceEntities3.GetContext();
            ЗаявкиDg.ItemsSource = _context.Заявки.ToList();

        }

        private void Home(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }

        private void Uslugi(object sender, RoutedEventArgs e)
        {
            new MainAdminWindow().Show();
            this.Close();
        }

        private void AddOrChange(object sender, RoutedEventArgs e)
        {
            if (ЗаявкиDg.SelectedItem != null)
            {
                new AddAdminWindow(ЗаявкиDg.SelectedItem as Заявки).Show();
                
            }
            else 
            {
                new AddAdminWindow().Show();
            }
        }

        private void Reload(object sender, RoutedEventArgs e)
        {
            _context = serviceEntities3.GetContext();
            ЗаявкиDg.ItemsSource = _context.Заявки.ToList();
        }

        private void Delete(object sender, RoutedEventArgs e)
        {
            _context.Заявки.Remove(ЗаявкиDg.SelectedItem as Заявки);
            _context.SaveChanges();
        }
    }
}
