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

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для OrderMenegerWindow.xaml
    /// </summary>
    public partial class OrderMenegerWindow : Window
    {
        public bootsEntities2 _context;
        public OrderMenegerWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgOrder.ItemsSource = _context.Заказ_import.ToList();
        }

        private void GoHomeButt(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }

        private void TovarWindow(object sender, RoutedEventArgs e)
        {
            new ManegerMainWindow().Show();
            this.Close();
        }
    }
}
