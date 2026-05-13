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
    /// Логика взаимодействия для OrderUserWindow.xaml
    /// </summary>
    public partial class OrderUserWindow : Window
    {
        public serviceEntities3 _context;
        public OrderUserWindow()
        {
            InitializeComponent();
            _context = serviceEntities3.GetContext();
            ЗаявкиDg.ItemsSource = _context.Заявки.Where(el => el.id_клиента == User.client.id).ToList();

        }

        private void Home(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }

        private void Uslugi(object sender, RoutedEventArgs e)
        {
            new MainUserWindow().Show();
            this.Close();
        }


    }
}
